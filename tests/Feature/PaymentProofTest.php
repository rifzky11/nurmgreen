<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'name' => 'Marl',
        ], $attributes));
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function orderFor(User $user, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id,
            'order_code' => 'NG-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            'total_price' => 90000,
            'status' => 'pending',
            'shipping_name' => 'Marl',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati No. 10, Jakarta',
            'notes' => null,
            'payment_proof' => null,
            'payment_proof_uploaded_at' => null,
        ], $attributes));
    }

    public function test_guest_and_non_owner_cannot_upload_proof(): void
    {
        Storage::fake('local');

        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $order = $this->orderFor($owner);
        $file = UploadedFile::fake()->image('bukti.jpg');

        $this->post(route('orders.upload-proof', $order), ['payment_proof' => $file])
            ->assertRedirect(route('login'));

        $this->actingAs($intruder)
            ->post(route('orders.upload-proof', $order), ['payment_proof' => $file])
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->post(route('orders.upload-proof', $order), ['payment_proof' => $file])
            ->assertForbidden();

        $this->assertNull($order->fresh()->payment_proof);
    }

    public function test_owner_cannot_upload_proof_for_non_pending_order(): void
    {
        Storage::fake('local');

        $owner = $this->customer();
        $order = $this->orderFor($owner, ['status' => 'paid']);

        $this->actingAs($owner)
            ->post(route('orders.upload-proof', $order), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertForbidden();

        $this->assertNull($order->fresh()->payment_proof);
    }

    public function test_upload_proof_validates_file_type_and_size(): void
    {
        Storage::fake('local');

        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)
            ->post(route('orders.upload-proof', $order), [])
            ->assertSessionHasErrors('payment_proof');

        $this->actingAs($customer)
            ->post(route('orders.upload-proof', $order), [
                'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('payment_proof');

        $this->actingAs($customer)
            ->post(route('orders.upload-proof', $order), [
                'payment_proof' => UploadedFile::fake()->image('besar.jpg')->size(3000),
            ])
            ->assertSessionHasErrors('payment_proof');

        $this->assertNull($order->fresh()->payment_proof);
    }

    public function test_owner_uploads_proof_to_private_disk_with_generated_filename(): void
    {
        Storage::fake('local');

        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)
            ->from(route('orders.show', $order))
            ->post(route('orders.upload-proof', $order), [
                'payment_proof' => UploadedFile::fake()->image('bukti-asli.png'),
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();

        $this->assertNotNull($order->payment_proof);
        $this->assertStringStartsWith('payment-proofs/', $order->payment_proof);
        $this->assertNotSame('payment-proofs/bukti-asli.png', $order->payment_proof);
        $this->assertNotNull($order->payment_proof_uploaded_at);
        Storage::disk('local')->assertExists($order->payment_proof);

        // Unggah bukti tidak mengubah status; hanya admin yang menandai paid.
        $this->assertSame('pending', $order->status);
    }

    public function test_reupload_deletes_the_old_file_first(): void
    {
        Storage::fake('local');

        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)->post(route('orders.upload-proof', $order), [
            'payment_proof' => UploadedFile::fake()->image('pertama.jpg'),
        ]);
        $oldPath = $order->fresh()->payment_proof;

        $this->actingAs($customer)->post(route('orders.upload-proof', $order), [
            'payment_proof' => UploadedFile::fake()->image('kedua.jpg'),
        ]);
        $newPath = $order->fresh()->payment_proof;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);
    }

    public function test_cancelling_order_keeps_the_proof_file(): void
    {
        Storage::fake('local');

        $customer = $this->customer();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)->post(route('orders.upload-proof', $order), [
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);
        $path = $order->fresh()->payment_proof;

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame($path, $order->fresh()->payment_proof);
        Storage::disk('local')->assertExists($path);
    }

    public function test_payment_proof_stream_is_limited_to_owner_and_admin(): void
    {
        Storage::fake('local');

        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $order = $this->orderFor($owner, [
            'payment_proof' => 'payment-proofs/bukti.jpg',
            'payment_proof_uploaded_at' => now(),
        ]);
        Storage::disk('local')->put('payment-proofs/bukti.jpg', 'fake-image');

        $this->get(route('orders.payment-proof', $order))->assertRedirect(route('login'));

        $this->actingAs($owner)->get(route('orders.payment-proof', $order))->assertOk();
        $this->actingAs($this->admin())->get(route('orders.payment-proof', $order))->assertOk();
        $this->actingAs($intruder)->get(route('orders.payment-proof', $order))->assertForbidden();
    }

    public function test_stream_returns_404_when_no_proof_exists(): void
    {
        $owner = $this->customer();
        $order = $this->orderFor($owner);

        $this->actingAs($owner)
            ->get(route('orders.payment-proof', $order))
            ->assertNotFound();
    }

    public function test_customer_order_detail_shows_upload_form_only_while_pending(): void
    {
        $customer = $this->customer();
        $pending = $this->orderFor($customer);
        $paid = $this->orderFor($customer, ['status' => 'paid']);

        $this->actingAs($customer)
            ->get(route('orders.show', $pending))
            ->assertOk()
            ->assertSee('Upload Bukti Pembayaran')
            ->assertSee('enctype="multipart/form-data"', false);

        $this->actingAs($customer)
            ->get(route('orders.show', $paid))
            ->assertOk()
            ->assertDontSee('enctype="multipart/form-data"', false);
    }

    public function test_upload_entry_points_exist_on_success_page_and_order_list(): void
    {
        $customer = $this->customer();
        $pending = $this->orderFor($customer);

        $this->actingAs($customer)
            ->get(route('checkout.success', $pending->order_code))
            ->assertOk()
            ->assertSee('Upload Bukti Pembayaran');

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Upload Bukti')
            ->assertSee('#upload-bukti', false);

        // Customer dengan pesanan paid saja tidak melihat tautan upload.
        $paidOnly = $this->customer(['name' => 'Paid Only']);
        $this->orderFor($paidOnly, ['status' => 'paid']);

        $this->actingAs($paidOnly)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertDontSee('Upload Bukti');
    }

    public function test_admin_list_shows_proof_badge_and_supports_filter(): void
    {
        $customer = $this->customer();
        $withProof = $this->orderFor($customer, [
            'payment_proof' => 'payment-proofs/a.jpg',
            'payment_proof_uploaded_at' => now(),
        ]);
        $withoutProof = $this->orderFor($customer);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Bukti terunggah');

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['proof' => '1']))
            ->assertOk()
            ->assertSee($withProof->order_code)
            ->assertDontSee($withoutProof->order_code);
    }

    public function test_admin_detail_shows_proof_or_empty_state(): void
    {
        $customer = $this->customer();
        $withProof = $this->orderFor($customer, [
            'payment_proof' => 'payment-proofs/a.jpg',
            'payment_proof_uploaded_at' => now(),
        ]);
        $withoutProof = $this->orderFor($customer);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $withProof))
            ->assertOk()
            ->assertSee('Lihat Ukuran Penuh')
            ->assertSee('Diunggah');

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $withoutProof))
            ->assertOk()
            ->assertSee('Belum ada bukti pembayaran.');
    }

    public function test_dashboard_counts_pending_orders_with_proof(): void
    {
        $customer = $this->customer();

        $this->orderFor($customer, ['payment_proof' => 'payment-proofs/a.jpg']);
        $this->orderFor($customer, ['payment_proof' => 'payment-proofs/b.jpg']);
        $this->orderFor($customer); // pending tanpa bukti
        $this->orderFor($customer, ['status' => 'paid', 'payment_proof' => 'payment-proofs/c.jpg']);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Pesanan dengan bukti');

        $this->assertSame(2, $response->viewData('pendingWithProof'));
    }
}
