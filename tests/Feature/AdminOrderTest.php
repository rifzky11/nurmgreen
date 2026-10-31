<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

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
            'stock' => 3,
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
            'notes' => null,
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

    public function test_guest_is_redirected_to_login(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
        $this->get(route('admin.orders.show', $order))->assertRedirect(route('login'));
        $this->patch(route('admin.orders.update-status', $order), ['status' => 'paid'])
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_order_management(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.orders.show', $order))->assertForbidden();
        $this->actingAs($customer)
            ->patch(route('admin.orders.update-status', $order), ['status' => 'paid'])
            ->assertForbidden();
    }

    public function test_admin_sees_orders_from_all_customers(): void
    {
        $first = $this->customer();
        $second = $this->customer(['name' => 'Ripski']);

        $firstOrder = $this->orderFor($first);
        $secondOrder = $this->orderFor($second);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($firstOrder->order_code)
            ->assertSee($secondOrder->order_code)
            ->assertSee('Marl')
            ->assertSee('Ripski');
    }

    public function test_admin_can_search_orders_by_order_code(): void
    {
        $customer = $this->customer();
        $matching = $this->orderFor($customer, [], ['order_code' => 'NG-20260101-AAAA']);
        $other = $this->orderFor($customer, [], ['order_code' => 'NG-20260101-BBBB']);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['q' => 'AAAA']))
            ->assertOk()
            ->assertSee($matching->order_code)
            ->assertDontSee($other->order_code);
    }

    public function test_admin_can_filter_orders_by_status(): void
    {
        $customer = $this->customer();
        $pending = $this->orderFor($customer, [], ['status' => 'pending']);
        $paid = $this->orderFor($customer, [], ['status' => 'paid']);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['status' => 'paid']))
            ->assertOk()
            ->assertSee($paid->order_code)
            ->assertDontSee($pending->order_code);
    }

    public function test_unknown_status_filter_is_ignored(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['status' => 'refund']))
            ->assertOk()
            ->assertSee($order->order_code);
    }

    public function test_orders_are_paginated_ten_per_page(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 50]);

        foreach (range(1, 11) as $i) {
            $this->orderFor($customer, [[$product, 1]]);
        }

        $pageOne = $this->actingAs($this->admin())->get(route('admin.orders.index'));
        $pageOne->assertOk();

        $this->assertCount(10, $pageOne->viewData('orders'));
        $this->assertSame(2, $pageOne->viewData('orders')->lastPage());

        $pageTwo = $this->actingAs($this->admin())->get(route('admin.orders.index', ['page' => 2]));
        $this->assertCount(1, $pageTwo->viewData('orders'));
    }

    public function test_detail_shows_customer_items_status_and_allowed_transitions(): void
    {
        $customer = $this->customer();
        $product = $this->product(['name' => 'Lidah Mertua']);
        $order = $this->orderFor($customer, [[$product, 2]]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Marl')
            ->assertSee($customer->email)
            ->assertSee('Lidah Mertua')
            ->assertSee('Menunggu Pembayaran')
            ->assertSee('<option value="paid">', false)
            ->assertSee('<option value="cancelled">', false)
            ->assertDontSee('<option value="shipped">', false);
    }

    public function test_pending_order_can_move_to_paid(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($customer, [[$product, 2]]);

        $this->actingAs($this->admin())
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update-status', $order), ['status' => 'paid'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_full_happy_path_pending_paid_shipped(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($customer, [[$product, 2]]);
        $admin = $this->admin();

        foreach (['paid', 'shipped'] as $status) {
            $this->actingAs($admin)
                ->patch(route('admin.orders.update-status', $order), ['status' => $status])
                ->assertSessionHas('success');

            $this->assertSame($status, $order->fresh()->status);
        }

        // Waktu kirim tercatat; admin tidak boleh menandai completed.
        $this->assertNotNull($order->fresh()->shipped_at);
        $this->assertNull($order->fresh()->completed_at);

        // Stok hanya berubah saat pembatalan, bukan saat maju status.
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_admin_cannot_mark_order_as_completed(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer, [], ['status' => 'shipped', 'shipped_at' => now()]);

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.update-status', $order), ['status' => 'completed'])
            ->assertSessionHasErrors('status');

        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertNull($order->fresh()->completed_at);
    }

    public function test_admin_detail_has_no_completed_option(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer, [], ['status' => 'paid']);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('<option value="shipped">', false)
            ->assertSee('<option value="cancelled">', false)
            ->assertDontSee('<option value="completed">', false);
    }

    public function test_admin_detail_shows_shipped_and_completed_timestamps(): void
    {
        $customer = $this->customer();

        $shipped = $this->orderFor($customer, [], [
            'status' => 'shipped',
            'shipped_at' => now()->subDay(),
        ]);
        $completed = $this->orderFor($customer, [], [
            'status' => 'completed',
            'shipped_at' => now()->subDays(2),
            'completed_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $shipped))
            ->assertOk()
            ->assertSee('Sedang Dikirim')
            ->assertSee('Dikirim '.$shipped->shipped_at->format('d/m/Y H:i'));

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $completed))
            ->assertOk()
            ->assertSee('Sudah Diterima')
            ->assertSee('Diterima '.$completed->completed_at->format('d/m/Y H:i'));
    }

    public function test_cancelling_pending_order_restores_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($customer, [[$product, 2]]);

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.update-status', $order), ['status' => 'cancelled'])
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_cancelling_paid_order_restores_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($customer, [[$product, 2]], ['status' => 'paid']);

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.update-status', $order), ['status' => 'cancelled'])
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_shipped_and_completed_orders_cannot_be_cancelled(): void
    {
        $customer = $this->customer();

        foreach (['shipped', 'completed'] as $status) {
            $product = $this->product(['stock' => 3]);
            $order = $this->orderFor($customer, [[$product, 2]], ['status' => $status]);

            $this->actingAs($this->admin())
                ->patch(route('admin.orders.update-status', $order), ['status' => 'cancelled'])
                ->assertSessionHasErrors('status');

            $this->assertSame($status, $order->fresh()->status);
            $this->assertSame(3, $product->fresh()->stock);
        }
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();

        $cases = [
            ['pending', 'shipped'],
            ['pending', 'completed'],
            ['pending', 'pending'],
            ['paid', 'completed'],
            ['paid', 'paid'],
            ['shipped', 'completed'],
            ['shipped', 'paid'],
            ['completed', 'paid'],
            ['cancelled', 'paid'],
        ];

        foreach ($cases as [$from, $to]) {
            $product = $this->product(['stock' => 3]);
            $order = $this->orderFor($customer, [[$product, 2]], ['status' => $from]);

            $this->actingAs($admin)
                ->patch(route('admin.orders.update-status', $order), ['status' => $to])
                ->assertSessionHasErrors('status');

            $this->assertSame($from, $order->fresh()->status, "Transisi {$from} -> {$to} seharusnya ditolak.");
            $this->assertSame(3, $product->fresh()->stock);
        }
    }

    public function test_unknown_status_value_is_rejected(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($this->admin())
            ->patch(route('admin.orders.update-status', $order), ['status' => 'refund'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_cancelling_twice_does_not_restock_twice(): void
    {
        $customer = $this->customer();
        $product = $this->product(['stock' => 3]);
        $order = $this->orderFor($customer, [[$product, 2]]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.orders.update-status', $order), ['status' => 'cancelled']);
        $this->assertSame(5, $product->fresh()->stock);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-status', $order->fresh()), ['status' => 'cancelled'])
            ->assertSessionHasErrors('status');

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_terminal_order_has_no_status_form(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer, [], ['status' => 'completed']);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('bersifat final')
            ->assertDontSee('name="status"', false);
    }

    public function test_unknown_order_code_returns_not_found(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', 'NG-00000000-XXXX'))
            ->assertNotFound();
    }
}
