<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderTest extends TestCase
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
            'price' => 45000,
            'stock' => 5,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @param  array<int, array{0: Product, 1: int}>  $items
     */
    private function orderFor(User $user, array $items = [], array $attributes = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'order_code' => 'NG-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            'total_price' => 90000,
            'status' => 'pending',
            'shipping_name' => 'Marl',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati No. 10, Jakarta',
            'notes' => 'Tolong dibungkus rapi.',
        ], $attributes));

        if ($items === []) {
            $items = [[$this->product(), 2]];
        }

        foreach ($items as [$product, $quantity]) {
            $order->orderItems()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'subtotal' => (float) $product->price * $quantity,
            ]);
        }

        return $order;
    }

    public function test_guest_cannot_access_orders(): void
    {
        $owner = $this->customer();
        $order = $this->orderFor($owner);

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->get(route('orders.show', $order))->assertRedirect(route('login'));
        $this->patch(route('orders.cancel', $order))->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_customer_orders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('orders.index'))->assertForbidden();
    }

    public function test_index_only_lists_orders_owned_by_logged_in_user(): void
    {
        $owner = $this->customer();
        $other = $this->customer(['name' => 'Ripski']);

        $mine = $this->orderFor($owner);
        $theirs = $this->orderFor($other);

        $this->actingAs($owner)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($mine->order_code)
            ->assertDontSee($theirs->order_code);
    }

    public function test_orders_are_paginated_ten_per_page(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 50]);

        foreach (range(1, 11) as $i) {
            $this->orderFor($customer, [[$product, 1]]);
        }

        $pageOne = $this->actingAs($customer)->get(route('orders.index'));
        $pageOne->assertOk()->assertSee('Halaman 1 dari 2')->assertSee('11 data');

        $this->assertCount(10, $pageOne->viewData('orders'));
        $this->assertSame(2, $pageOne->viewData('orders')->lastPage());

        $pageTwo = $this->actingAs($customer)->get(route('orders.index', ['page' => 2]));
        $this->assertCount(1, $pageTwo->viewData('orders'));
    }

    public function test_owner_can_view_order_detail_with_items_and_status(): void
    {
        $customer = $this->customer();
        $product = $this->product(['name' => 'Lidah Mertua', 'price' => 45000]);
        $order = $this->orderFor($customer, [[$product, 2]]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Menunggu Pembayaran')
            ->assertSee('Lidah Mertua')
            ->assertSee('Rp 90.000')
            ->assertSee('Jl. Melati No. 10, Jakarta');
    }

    public function test_customer_cannot_view_another_users_order(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $order = $this->orderFor($owner);

        $this->actingAs($intruder)
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_cancelling_pending_order_restores_stock_and_marks_it_cancelled(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($customer, [[$product, 2]]);

        $this->actingAs($customer)
            ->from(route('orders.show', $order))
            ->patch(route('orders.cancel', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_cancellation_restores_stock_for_every_item(): void
    {
        $customer = $this->customer();
        $first = $this->product(['name' => 'Monstera', 'stock' => 3]);
        $second = $this->product(['name' => 'Kaktus', 'stock' => 4]);

        $order = $this->orderFor($customer, [[$first, 2], [$second, 3]]);

        $this->actingAs($customer)->patch(route('orders.cancel', $order));

        $this->assertSame(5, $first->fresh()->stock);
        $this->assertSame(7, $second->fresh()->stock);
    }

    public function test_customer_cannot_cancel_another_users_order(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($owner, [[$product, 2]]);

        $this->actingAs($intruder)
            ->patch(route('orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_non_pending_order_cannot_be_cancelled(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($customer, [[$product, 2]], ['status' => 'paid']);

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_cancelling_twice_does_not_restock_twice(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($customer, [[$product, 2]]);

        $this->actingAs($customer)->patch(route('orders.cancel', $order));

        $this->assertSame(5, $product->fresh()->stock);

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order->fresh()))
            ->assertForbidden();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_cancel_button_only_appears_for_pending_orders(): void
    {
        $customer = $this->customer();
        $pending = $this->orderFor($customer);
        $paid = $this->orderFor($customer, [], ['status' => 'paid']);

        $this->actingAs($customer)
            ->get(route('orders.show', $pending))
            ->assertSee('Batalkan Pesanan');

        $this->actingAs($customer)
            ->get(route('orders.show', $paid))
            ->assertDontSee('Batalkan Pesanan');
    }
}
