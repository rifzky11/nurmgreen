<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderReceivedTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'name' => 'Marl',
        ], $attributes));
    }

    private function orderFor(User $user, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id,
            'order_code' => 'NG-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            'total_price' => 90000,
            'status' => 'shipped',
            'shipping_name' => 'Marl',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati No. 10, Jakarta',
            'notes' => null,
            'shipped_at' => now()->subHour(),
        ], $attributes));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $order = $this->orderFor($this->customer());

        $this->patch(route('orders.received', $order))->assertRedirect(route('login'));
    }

    public function test_admin_cannot_mark_order_received(): void
    {
        $order = $this->orderFor($this->customer());

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('orders.received', $order))
            ->assertForbidden();
    }

    public function test_owner_can_only_confirm_when_status_is_shipped(): void
    {
        $customer = $this->customer();

        foreach (['pending', 'paid', 'completed', 'cancelled'] as $status) {
            $order = $this->orderFor($customer, ['status' => $status]);

            $this->actingAs($customer)
                ->patch(route('orders.received', $order))
                ->assertForbidden();

            $this->assertSame($status, $order->fresh()->status);
            $this->assertNull($order->fresh()->completed_at);
        }
    }

    public function test_other_customer_cannot_mark_order_received(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $order = $this->orderFor($owner);

        $this->actingAs($intruder)
            ->patch(route('orders.received', $order))
            ->assertForbidden();

        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_owner_confirms_received_and_sets_completed_at(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)
            ->from(route('orders.show', $order))
            ->patch(route('orders.received', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success', 'Terima kasih, pesanan telah dikonfirmasi diterima.');

        $order->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_buyer_status_input_is_ignored_by_received_endpoint(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)
            ->patch(route('orders.received', $order), ['status' => 'cancelled'])
            ->assertSessionHas('success');

        // Endpoint tidak menerima status dari input; server menetapkan completed.
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_buyer_cannot_cancel_shipped_order(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_shipped_detail_shows_received_button_and_timeline(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer, ['shipped_at' => now()->subHours(2)]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Pesanan Sudah Diterima')
            ->assertSee('status-stepper', false)
            ->assertSee('Menunggu Pembayaran')
            ->assertSee('Dibayar')
            ->assertSee('Sedang Dikirim')
            ->assertSee('Sudah Diterima')
            ->assertSee('status-primary', false)
            ->assertSee($order->shipped_at->format('d/m/Y H:i'));
    }

    public function test_pending_detail_has_no_received_button(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer, ['status' => 'pending', 'shipped_at' => null]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Pesanan Sudah Diterima');
    }

    public function test_cancelled_detail_shows_single_cancelled_state_instead_of_stepper(): void
    {
        $customer = $this->customer();
        $order = $this->orderFor($customer, ['status' => 'cancelled']);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('status-cancelled-state', false)
            ->assertSee('Pesanan ini telah dibatalkan.')
            ->assertSee('status-neutral', false)
            ->assertDontSee('status-stepper', false);
    }

    public function test_customer_list_uses_updated_status_labels_and_colors(): void
    {
        $customer = $this->customer();
        $this->orderFor($customer, ['status' => 'shipped']);
        $this->orderFor($customer, ['status' => 'completed', 'completed_at' => now()]);

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Sedang Dikirim')
            ->assertSee('Sudah Diterima')
            ->assertSee('status-primary', false)
            ->assertSee('status-success', false);
    }
}
