<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->put(route('profile.update'), ['name' => 'X'])->assertRedirect(route('login'));
        $this->put(route('profile.password'), [])->assertRedirect(route('login'));
    }

    public function test_customer_and_admin_can_open_profile_page(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Data Profil')
            ->assertSee('Ubah Password')
            ->assertSee('value="Marl"', false)
            ->assertSee('value="081234567890"', false)
            ->assertSee('Jl. Melati No. 10, Jakarta');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_customer_can_update_profile_fields(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => 'Marl Baru',
                'phone' => '081298765432',
                'address' => 'Jl. Baru No. 5, Bandung',
                // Email & role tidak boleh berubah dari input.
                'email' => 'hacker@example.com',
                'role' => 'admin',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $customer->refresh();

        $this->assertSame('Marl Baru', $customer->name);
        $this->assertSame('081298765432', $customer->phone);
        $this->assertSame('Jl. Baru No. 5, Bandung', $customer->address);
        $this->assertSame('customer', $customer->role);
        $this->assertNotSame('hacker@example.com', $customer->email);
    }

    public function test_profile_fields_are_validated(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->put(route('profile.update'), [
                'name' => '',
                'phone' => 'bukan-nomor',
                'address' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors(['name', 'phone', 'address']);

        $this->assertSame('Marl', $customer->fresh()->name);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->put(route('profile.password'), [
                'current_password' => 'password-salah',
                'password' => 'newsecret123',
                'password_confirmation' => 'newsecret123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $customer->fresh()->password));
    }

    public function test_new_password_must_be_confirmed_and_long_enough(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->put(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_customer_can_change_password(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->from(route('profile.edit'))
            ->put(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'newsecret123',
                'password_confirmation' => 'newsecret123',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('newsecret123', $customer->fresh()->password));
    }
}
