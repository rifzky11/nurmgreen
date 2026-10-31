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
            ->post(route('cart.store', $product), ['quantity' => 2])
            ->assertRedirect(route('cart.index'))
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
            ->assertSee('<span class="cart-count">5</span>', false);
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
}
