<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_uses_the_custom_nurmgreen_page(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('nurmgreen');
    }

    public function test_403_uses_the_custom_nurmgreen_page(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('Akses Ditolak')
            ->assertSee('Kembali ke Beranda');
    }

    public function test_419_error_view_renders(): void
    {
        $this->view('errors.419')
            ->assertSee('419')
            ->assertSee('Sesi Telah Berakhir')
            ->assertSee('Token keamanan (CSRF) sudah kedaluwarsa');
    }

    public function test_500_error_view_renders(): void
    {
        $this->view('errors.500')
            ->assertSee('500')
            ->assertSee('Terjadi Kesalahan')
            ->assertSee('Kembali ke Beranda');
    }

    public function test_env_template_disables_debug_for_production(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_DEBUG=false', $example);
    }
}
