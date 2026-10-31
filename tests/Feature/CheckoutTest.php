<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'name' => 'Marl',
            'phone' => '081234567890',
            'address' => 'Jl. Melati No. 10, Jakarta',
        ], $attributes));
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
            'stock' => 10,
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

    private function shippingData(array $overrides = []): array
    {
        return array_merge([
            'shipping_name' => 'Marl',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati No. 10, Jakarta',
            'notes' => 'Tolong dibungkus rapi.',
        ], $overrides);
    }

    public function test_guest_cannot_access_checkout(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('login'));
        $this->post(route('checkout.store'), $this->shippingData())->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_checkout(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('checkout.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('checkout.store'), $this->shippingData())->assertForbidden();
    }

    public function test_empty_cart_redirects_to_cart_page(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->actingAs($customer)
            ->post(route('checkout.store'), $this->shippingData())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_form_is_prefilled_with_user_profile_and_cart_summary(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->item($customer, $product, 2);

        $this->actingAs($customer)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('value="Marl"', false)
            ->assertSee('value="081234567890"', false)
            ->assertSee('Jl. Melati No. 10, Jakarta')
            ->assertSee('Monstera')
            ->assertSee('Rp 100.000');
    }

    public function test_successful_checkout_creates_order_snapshot_deducts_stock_and_clears_cart(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 10, 'price' => 45000]);
        $this->item($customer, $product, 3);

        $response = $this->actingAs($customer)->post(route('checkout.store'), $this->shippingData([
            'notes' => null,
        ]));

        $order = Order::sole();

        $response
            ->assertRedirect(route('checkout.success', $order->order_code))
            ->assertSessionHas('success');

        $this->assertMatchesRegularExpression('/^NG-\d{8}-[A-Z0-9]{4}$/', $order->order_code);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame('pending', $order->status);
        $this->assertSame(135000.0, (float) $order->total_price);
        $this->assertSame('Marl', $order->shipping_name);
        $this->assertSame('081234567890', $order->shipping_phone);
        $this->assertSame('Jl. Melati No. 10, Jakarta', $order->shipping_address);
        $this->assertNull($order->notes);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Monstera',
            'price' => 45000,
            'quantity' => 3,
            'subtotal' => 135000,
        ]);

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_total_is_computed_server_side_and_ignores_client_price_input(): void
    {
        $customer = $this->customer();
        $product = $this->product(['price' => 20000]);
        $this->item($customer, $product, 2);

        $this->actingAs($customer)->post(route('checkout.store'), $this->shippingData([
            'total_price' => 1,
            'price' => 1,
            'subtotal' => 1,
        ]));

        $this->assertSame(40000.0, (float) Order::sole()->total_price);
    }

    public function test_insufficient_stock_aborts_checkout_and_keeps_everything_unchanged(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 2]);
        $this->item($customer, $product, 5);

        $this->actingAs($customer)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), $this->shippingData())
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_inactive_product_blocks_checkout(): void
    {
        $customer = $this->customer();
        $product = $this->product(['is_active' => false]);
        $this->item($customer, $product, 1);

        $this->actingAs($customer)
            ->post(route('checkout.store'), $this->shippingData())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_one_insufficient_item_rolls_back_the_whole_checkout(): void
    {
        $customer = $this->customer();
        $available = $this->product(['name' => 'Lidah Mertua', 'stock' => 5]);
        $scarce = $this->product(['name' => 'Kaktus', 'stock' => 1]);

        $this->item($customer, $available, 2);
        $this->item($customer, $scarce, 3);

        $this->actingAs($customer)
            ->post(route('checkout.store'), $this->shippingData())
            ->assertSessionHas('error');

        // Tidak ada order/order_items setengah jadi, stok kedua produk utuh,
        // dan keranjang tetap ada agar user bisa memperbaiki jumlahnya.
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(5, $available->fresh()->stock);
        $this->assertSame(1, $scarce->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_shipping_fields_are_required(): void
    {
        $customer = $this->customer();
        $this->item($customer, $this->product(), 1);

        $this->actingAs($customer)
            ->post(route('checkout.store'), [])
            ->assertSessionHasErrors(['shipping_name', 'shipping_phone', 'shipping_address']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_phone_number_format_is_validated(): void
    {
        $customer = $this->customer();
        $this->item($customer, $this->product(), 1);

        $this->actingAs($customer)
            ->post(route('checkout.store'), $this->shippingData(['shipping_phone' => 'bukan-nomor']))
            ->assertSessionHasErrors('shipping_phone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_success_page_shows_order_code_and_transfer_instructions(): void
    {
        $customer = $this->customer();
        $product = $this->product(['price' => 45000]);
        $this->item($customer, $product, 2);

        $this->actingAs($customer)->post(route('checkout.store'), $this->shippingData());
        $order = Order::sole();

        $this->actingAs($customer)
            ->get(route('checkout.success', $order->order_code))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('BCA')
            ->assertSee('1234567890')
            ->assertSee('Rp 90.000')
            ->assertSee('Instruksi Pembayaran');
    }

    public function test_customer_cannot_view_another_users_order_success_page(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $this->item($owner, $this->product(), 1);

        $this->actingAs($owner)->post(route('checkout.store'), $this->shippingData());
        $order = Order::sole();

        $this->actingAs($intruder)
            ->get(route('checkout.success', $order->order_code))
            ->assertNotFound();
    }

    public function test_order_items_keep_snapshot_after_product_is_edited(): void
    {
        $customer = $this->customer();
        $product = $this->product(['name' => 'Monstera', 'price' => 45000]);
        $this->item($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), $this->shippingData());

        $product->update(['name' => 'Monstera Baru', 'price' => 99000]);

        $item = OrderItem::sole();

        $this->assertSame('Monstera', $item->product_name);
        $this->assertSame(45000.0, (float) $item->price);
        $this->assertSame(45000.0, (float) Order::sole()->total_price);
    }

    public function test_order_codes_are_unique_per_order(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 20]);

        $this->item($customer, $product, 1);
        $this->actingAs($customer)->post(route('checkout.store'), $this->shippingData());

        $this->item($customer, $product, 2);
        $this->actingAs($customer)->post(route('checkout.store'), $this->shippingData());

        $codes = Order::pluck('order_code');

        $this->assertCount(2, $codes);
        $this->assertSame($codes->count(), $codes->unique()->count());
        $codes->each(fn (string $code) => $this->assertMatchesRegularExpression('/^NG-\d{8}-[A-Z0-9]{4}$/', $code));
    }

    public function test_checkout_page_can_be_limited_to_selected_cart_items(): void
    {
        $customer = $this->customer();
        $first = $this->product(['name' => 'Monstera', 'price' => 50000]);
        $second = $this->product(['name' => 'Lidah Mertua', 'price' => 20000]);

        $this->item($customer, $first, 1);
        $selected = $this->item($customer, $second, 2);

        $this->actingAs($customer)
            ->get(route('checkout.index', ['items' => [$selected->id]]))
            ->assertOk()
            ->assertSee('Lidah Mertua')
            ->assertDontSee('Monstera')
            ->assertSee('Rp 40.000')
            ->assertDontSee('Rp 50.000');
    }

    public function test_selected_checkout_only_processes_selected_items(): void
    {
        $customer = $this->customer();
        $first = $this->product(['name' => 'Monstera', 'price' => 50000, 'stock' => 5]);
        $second = $this->product(['name' => 'Lidah Mertua', 'price' => 20000, 'stock' => 5]);

        $selected = $this->item($customer, $first, 1);
        $unselected = $this->item($customer, $second, 2);

        $this->actingAs($customer)->post(route('checkout.store'), $this->shippingData([
            'items' => [$selected->id],
        ]));

        $order = Order::sole();

        $this->assertSame(50000.0, (float) $order->total_price);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $first->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('order_items', ['product_id' => $second->id]);

        $this->assertSame(4, $first->fresh()->stock);
        $this->assertSame(5, $second->fresh()->stock);

        // Item yang tidak dicentang tetap tinggal di keranjang.
        $this->assertDatabaseMissing('cart_items', ['id' => $selected->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $unselected->id, 'quantity' => 2]);
    }

    public function test_checkout_rejects_cart_item_ids_owned_by_another_user(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $ownerItem = $this->item($owner, $this->product(['stock' => 5]), 1);
        $this->item($intruder, $this->product(['stock' => 5]), 1);

        $this->actingAs($intruder)
            ->post(route('checkout.store'), $this->shippingData([
                'items' => [$ownerItem->id],
            ]))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', ['id' => $ownerItem->id]);
    }

    public function test_checkout_rejects_unknown_selected_item(): void
    {
        $customer = $this->customer();
        $this->item($customer, $this->product(), 1);

        $this->actingAs($customer)
            ->get(route('checkout.index', ['items' => [999999]]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    public function test_checkout_prefills_default_saved_address(): void
    {
        $customer = $this->customer();
        $this->item($customer, $this->product(), 1);

        Address::create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'recipient_name' => 'Penerima Alamat',
            'phone' => '081200000002',
            'address' => 'Jl. Alamat Utama No. 1, Jakarta',
            'is_default' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Alamat Tersimpan')
            ->assertSee('value="Penerima Alamat"', false)
            ->assertSee('value="081200000002"', false)
            ->assertSee('Jl. Alamat Utama No. 1, Jakarta')
            ->assertSee('Kelola Alamat');
    }
}
