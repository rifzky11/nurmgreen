<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $this->assertSame('customer', $user->fresh()->role);
    }

    public function test_registration_ignores_role_in_request(): void
    {
        $this->post(route('register'), [
            'name' => 'User Baru',
            'email' => 'userbaru@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ])->assertRedirect(route('home'));

        $this->assertSame('customer', User::where('email', 'userbaru@example.com')->value('role'));
    }

    public function test_all_non_get_forms_have_csrf_token(): void
    {
        $missing = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $content = $file->getContents();
            preg_match_all('/<form\b.*?<\/form>/is', $content, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$block, $offset]) {
                if (preg_match('/method=["\']get["\']/i', $block)) {
                    continue; // form pencarian GET tidak butuh token
                }

                if (! str_contains($block, '@csrf')) {
                    $line = substr_count(substr($content, 0, $offset), "\n") + 1;
                    $missing[] = $file->getRelativePathname().':'.$line;
                }
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_views_do_not_use_unescaped_echo(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')
                && str_contains($file->getContents(), '{!!')) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_application_code_uses_no_raw_sql(): void
    {
        $patterns = ['DB::raw(', 'selectRaw(', 'whereRaw(', 'orderByRaw(', 'havingRaw(', 'DB::statement('];
        $offenders = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (str_contains($file->getContents(), $pattern)) {
                    $offenders[] = $file->getRelativePathname().' -> '.$pattern;
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_confirm_dialogs_do_not_embed_user_controlled_names(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $category = Category::create([
            'name' => 'Tanaman Hias',
            'slug' => 'tanaman-hias-audit',
            'description' => 'Kategori audit.',
        ]);

        // Nama produk sengaja mengandung kutip agar tidak boleh masuk ke JS inline.
        $product = Product::create([
            'category_id' => $category->id,
            'name' => "Monstera'); alert('xss'); //",
            'slug' => 'monstera-audit',
            'description' => 'Produk audit.',
            'price' => 10000,
            'stock' => 5,
            'is_active' => true,
        ]);

        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee("confirm('Hapus produk ini dari keranjang?')", false);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertSee("confirm('Hapus produk ini?')", false);
    }
}
