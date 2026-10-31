<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'name' => 'Marl']);
    }

    private function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'tanaman-hias'],
            ['name' => 'Tanaman Hias', 'description' => 'Tanaman untuk dekorasi rumah.']
        );

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Monstera '.Str::random(4),
            'slug' => 'monstera-'.uniqid(),
            'description' => 'Tanaman daun lebar.',
            'price' => 45000,
            'stock' => 10,
            'is_active' => true,
        ], $attributes));
    }

    private function orderFor(
        User $user,
        string $status = 'pending',
        float $total = 100000,
        ?string $code = null,
        ?\DateTimeInterface $createdAt = null
    ): Order {
        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => $code ?? 'NG-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            'total_price' => $total,
            'status' => $status,
            'shipping_name' => 'Marl',
            'shipping_phone' => '081234567890',
            'shipping_address' => 'Jl. Melati No. 10, Jakarta',
            'notes' => null,
        ]);

        if ($createdAt !== null) {
            $order->timestamps = false;
            $order->forceFill(['created_at' => $createdAt])->save();
            $order->timestamps = true;
        }

        return $order;
    }

    public function test_guest_is_redirected_and_customer_is_forbidden(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($this->customer())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_shows_aggregate_counts_and_revenue(): void
    {
        $customer = $this->customer();

        // Dua kategori, dua produk.
        Category::create(['name' => 'Pupuk', 'slug' => 'pupuk', 'description' => 'Pupuk tanaman.']);
        $this->product();
        $this->product(['stock' => 20]);

        // Omzet = 100.000 + 50.000 + 25.000 = 175.000.
        $this->orderFor($customer, 'paid', 100000);
        $this->orderFor($customer, 'shipped', 50000);
        $this->orderFor($customer, 'completed', 25000);
        $this->orderFor($customer, 'pending', 90000);
        $this->orderFor($customer, 'cancelled', 70000);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('totalProducts'));
        $this->assertSame(2, $response->viewData('totalCategories'));
        $this->assertSame(5, $response->viewData('totalOrders'));
        $this->assertSame(1, $response->viewData('pendingOrders'));
        $this->assertSame(175000.0, $response->viewData('totalRevenue'));

        $response
            ->assertSee('Total Produk')
            ->assertSee('Total Kategori')
            ->assertSee('Total Pesanan')
            ->assertSee('Omzet')
            ->assertSee('Rp 175.000')
            ->assertSee('Menunggu Pembayaran');
    }

    public function test_revenue_excludes_pending_and_cancelled_orders(): void
    {
        $customer = $this->customer();

        $this->orderFor($customer, 'paid', 100000);
        $this->orderFor($customer, 'pending', 888888);
        $this->orderFor($customer, 'cancelled', 999999);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $this->assertSame(100000.0, $response->viewData('totalRevenue'));
    }

    public function test_recent_orders_are_limited_to_five_newest_with_eager_loaded_users(): void
    {
        $customer = $this->customer(['name' => 'Ripski']);

        foreach (range(1, 6) as $i) {
            $this->orderFor($customer, 'pending', 1000, 'ORD-'.$i, now()->subMinutes($i));
        }

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));
        $recent = $response->viewData('recentOrders');

        $this->assertCount(5, $recent);
        $this->assertSame(
            ['ORD-1', 'ORD-2', 'ORD-3', 'ORD-4', 'ORD-5'],
            $recent->pluck('order_code')->all()
        );

        // Eager loading: relasi user sudah dimuat sekali untuk semua baris,
        // dan withCount menyertakan jumlah item tanpa query per pesanan.
        foreach ($recent as $order) {
            $this->assertTrue($order->relationLoaded('user'));
            $this->assertArrayHasKey('order_items_count', $order->getAttributes());
        }

        $response->assertDontSee('ORD-6');
    }

    public function test_low_stock_products_are_filtered_ordered_and_eager_loaded(): void
    {
        $this->product(['name' => 'Stok Sepuluh', 'stock' => 10]);
        $this->product(['name' => 'Stok Lima', 'stock' => 5]);
        $this->product(['name' => 'Stok Nol', 'stock' => 0]);
        $this->product(['name' => 'Stok Tiga', 'stock' => 3]);
        $this->product(['name' => 'Stok Enam', 'stock' => 6]);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));
        $lowStock = $response->viewData('lowStockProducts');

        $this->assertSame([0, 3, 5], $lowStock->pluck('stock')->all());

        foreach ($lowStock as $product) {
            $this->assertTrue($product->relationLoaded('category'));
        }

        $response
            ->assertSee('Habis')
            ->assertSee('Sisa 3')
            ->assertDontSee('Stok Enam')
            ->assertDontSee('Stok Sepuluh');
    }

    public function test_dashboard_handles_empty_data(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Belum ada pesanan masuk.')
            ->assertSee('Semua produk memiliki stok di atas 5.');

        $this->assertSame(0, $response->viewData('totalProducts'));
        $this->assertSame(0, $response->viewData('totalOrders'));
        $this->assertSame(0.0, $response->viewData('totalRevenue'));
        $this->assertCount(0, $response->viewData('recentOrders'));
        $this->assertCount(0, $response->viewData('lowStockProducts'));
    }

    public function test_pending_card_links_to_filtered_order_list(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertSee('admin/orders?status=pending', false);
    }
}
