<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'tanaman-hias'],
            ['name' => 'Tanaman Hias', 'description' => 'Tanaman untuk dekorasi rumah.']
        );

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Monstera',
            'slug' => 'monstera-'.uniqid(),
            'description' => 'Tanaman daun lebar.',
            'price' => 50000,
            'stock' => 5,
            'is_active' => true,
        ], $attributes));
    }

    private function item(User $user, Product $product, int $quantity): CartItem
    {
        return CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cart.index'))->assertRedirect(route('login'));
    }

    public function test_admin_cannot_open_the_cart(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('cart.index'))->assertForbidden();
    }

    public function test_customer_sees_empty_cart(): void
    {
        $this->actingAs($this->customer())
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Keranjang kamu masih kosong');
    }

    public function test_customer_can_add_product_to_cart(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer)
            ->from(route('catalog.show', $product))
            ->post(route('cart.store', $product), ['quantity' => 2])
            ->assertRedirect(route('catalog.show', $product))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_product_twice_increments_quantity(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 10]);

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 2]);
        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 3]);

        $this->assertSame(1, CartItem::count());
        $this->assertDatabaseHas('cart_items', ['quantity' => 5]);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);

        $this->actingAs($customer)
            ->post(route('cart.store', $product), ['quantity' => 4])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_increment_cannot_push_quantity_past_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 5]);

        $this->actingAs($customer)->post(route('cart.store', $product), ['quantity' => 3]);
        $this->actingAs($customer)
            ->post(route('cart.store', $product), ['quantity' => 3])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_inactive_or_out_of_stock_product_cannot_be_added(): void
    {
        $customer = $this->customer();
        $inactive = $this->product(['name' => 'Produk Nonaktif', 'is_active' => false]);
        $empty = $this->product(['name' => 'Produk Habis', 'stock' => 0]);

        $this->actingAs($customer)
            ->post(route('cart.store', $inactive), ['quantity' => 1])
            ->assertSessionHas('error');

        $this->actingAs($customer)
            ->post(route('cart.store', $empty), ['quantity' => 1])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_quantity_must_be_a_positive_integer(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer)
            ->post(route('cart.store', $product), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->actingAs($customer)
            ->post(route('cart.store', $product), ['quantity' => 'dua'])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_customer_id_is_taken_from_session_not_request(): void
    {
        $customer = $this->customer();
        $other = $this->customer();
        $product = $this->product();

        $this->actingAs($customer)->post(route('cart.store', $product), [
            'quantity' => 1,
            'user_id' => $other->id,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseMissing('cart_items', ['user_id' => $other->id]);
    }

    public function test_cart_only_lists_items_owned_by_the_logged_in_user(): void
    {
        $customer = $this->customer();
        $other = $this->customer();

        $mine = $this->product(['name' => 'Sri Rejeki']);
        $theirs = $this->product(['name' => 'Kaktus']);

        $this->item($customer, $mine, 2);   // 2 x 50.000 = 100.000
        $this->item($other, $theirs, 1);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Sri Rejeki')
            ->assertDontSee('Kaktus')
            ->assertSee('Rp 100.000');
    }

    public function test_customer_can_update_own_item_quantity(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 5]);
        $item = $this->item($customer, $product, 1);

        $this->actingAs($customer)
            ->patch(route('cart.update', $item), ['quantity' => 4])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 4]);
    }

    public function test_update_quantity_cannot_exceed_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 5]);
        $item = $this->item($customer, $product, 2);

        $this->actingAs($customer)
            ->patch(route('cart.update', $item), ['quantity' => 6])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 2]);
    }

    public function test_update_rejects_invalid_quantity(): void
    {
        $customer = $this->customer();
        $item = $this->item($customer, $this->product(), 2);

        $this->actingAs($customer)
            ->patch(route('cart.update', $item), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 2]);
    }

    public function test_customer_cannot_update_another_users_item(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer();
        $item = $this->item($owner, $this->product(), 2);

        $this->actingAs($intruder)
            ->patch(route('cart.update', $item), ['quantity' => 4])
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 2]);
    }

    public function test_customer_can_remove_own_item(): void
    {
        $customer = $this->customer();
        $item = $this->item($customer, $this->product(), 1);

        $this->actingAs($customer)
            ->delete(route('cart.destroy', $item))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_customer_cannot_remove_another_users_item(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer();
        $item = $this->item($owner, $this->product(), 1);

        $this->actingAs($intruder)
            ->delete(route('cart.destroy', $item))
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_navbar_shows_total_quantity_for_customer(): void
    {
        $customer = $this->customer();
        $first = $this->product(['name' => 'Monstera', 'stock' => 10]);
        $second = $this->product(['name' => 'Lidah Mertua', 'stock' => 10]);

        $this->item($customer, $first, 2);
        $this->item($customer, $second, 3);

        $this->actingAs($customer)
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('id="cartCountBadge"', false)
            ->assertSee('>5</span>', false);
    }

    public function test_navbar_hides_count_for_guest_and_admin(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('cart-count', false);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('cart-count', false);
    }

    public function test_cart_rows_have_checkboxes_for_selective_checkout(): void
    {
        $customer = $this->customer();
        $item = $this->item($customer, $this->product(), 2);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('name="items[]"', false)
            ->assertSee('value="'.$item->id.'"', false)
            ->assertSee('form="checkout-form"', false)
            ->assertSee('data-subtotal="100000"', false)
            ->assertSee('data-quantity="2"', false)
            ->assertSee('id="summaryLabel"', false)
            ->assertSee('id="summarySubtotal"', false)
            ->assertSee('id="summaryTotal"', false)
            ->assertSee('id="cartTypeCount"', false)
            ->assertSee('Checkout')
            ->assertSee('id="selectAll"', false);
    }

    public function test_cart_subtotal_sums_all_rows_without_row_value_overwriting_it(): void
    {
        $customer = $this->customer();
        $first = $this->product(['name' => 'Monstera', 'price' => 50000]);
        $second = $this->product(['name' => 'Lidah Mertua', 'price' => 20000]);

        $this->item($customer, $first, 1);  // 50.000
        $this->item($customer, $second, 2); // 40.000

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Subtotal (3 barang)')
            ->assertSee('Rp 90.000'); // total seluruh baris, bukan subtotal baris terakhir
    }

    public function test_quantity_update_via_ajax_returns_line_and_cart_totals(): void
    {
        $customer = $this->customer();
        $product = $this->product(['price' => 50000, 'stock' => 5]);
        $item = $this->item($customer, $product, 1);

        $this->actingAs($customer)
            ->patchJson(route('cart.update', $item), ['quantity' => 3])
            ->assertOk()
            ->assertJson([
                'quantity' => 3,
                'line_subtotal' => 150000,
                'formatted_line_subtotal' => 'Rp 150.000',
                'cart_quantity' => 3,
                'max_quantity' => 5,
            ]);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);
    }

    public function test_ajax_quantity_update_returns_error_when_stock_is_insufficient(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 2]);
        $item = $this->item($customer, $product, 1);

        $this->actingAs($customer)
            ->patchJson(route('cart.update', $item), ['quantity' => 3])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_cart_has_no_manual_ubah_button(): void
    {
        $customer = $this->customer();
        $this->item($customer, $this->product(), 1);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-qty-form', false)
            ->assertSee('cart-line-error', false)
            ->assertDontSee('>Ubah</button>', false);
    }

    public function test_add_to_cart_via_ajax_returns_success_and_cart_count(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 5]);
        $this->item($customer, $this->product(), 2); // item lain: 2 barang

        $this->actingAs($customer)
            ->postJson(route('cart.store', $product), ['quantity' => 3])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'cart_count' => 5,
                'message' => 'Produk ditambahkan ke keranjang.',
            ]);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_add_to_cart_via_ajax_returns_stock_error(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 2]);

        $this->actingAs($customer)
            ->postJson(route('cart.store', $product), ['quantity' => 3])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_add_to_cart_via_ajax_returns_error_for_inactive_product(): void
    {
        $customer = $this->customer();
        $product = $this->product(['is_active' => false]);

        $this->actingAs($customer)
            ->postJson(route('cart.store', $product), ['quantity' => 1])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Produk ini sedang tidak tersedia.',
            ]);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_layout_exposes_csrf_meta_and_toast_container(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('name="csrf-token"', false)
            ->assertSee('id="toast-container"', false);
    }

    public function test_product_detail_has_ajax_add_to_cart_form_for_customers(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer)
            ->get(route('catalog.show', $product))
            ->assertOk()
            ->assertSee('js-add-to-cart', false)
            ->assertSee('data-login-url', false);
    }
}
