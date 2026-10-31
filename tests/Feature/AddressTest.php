<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'name' => 'Marl',
        ], $attributes));
    }

    private function addressFor(User $user, array $attributes = []): Address
    {
        return Address::create(array_merge([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => 'Marl',
            'phone' => '081234567890',
            'address' => 'Jl. Melati No. 10, Jakarta',
            'is_default' => false,
        ], $attributes));
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Rumah',
            'recipient_name' => 'Marl',
            'phone' => '081234567890',
            'address' => 'Jl. Melati No. 10, Jakarta',
        ], $overrides);
    }

    public function test_guest_is_redirected_and_admin_is_forbidden(): void
    {
        $this->get(route('addresses.index'))->assertRedirect(route('login'));
        $this->post(route('addresses.store'), $this->data())->assertRedirect(route('login'));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('addresses.index'))
            ->assertForbidden();
    }

    public function test_customer_only_sees_own_addresses(): void
    {
        $owner = $this->customer();
        $other = $this->customer(['name' => 'Ripski']);

        $this->addressFor($owner, ['label' => 'Rumah Marl']);
        $this->addressFor($other, ['label' => 'Rumah Ripski']);

        $this->actingAs($owner)
            ->get(route('addresses.index'))
            ->assertOk()
            ->assertSee('Rumah Marl')
            ->assertDontSee('Rumah Ripski');
    }

    public function test_first_address_becomes_default_and_later_ones_do_not(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('addresses.store'), $this->data(['label' => 'Rumah']));
        $this->actingAs($customer)->post(route('addresses.store'), $this->data(['label' => 'Kantor']));

        $this->assertDatabaseHas('addresses', ['label' => 'Rumah', 'is_default' => 1]);
        $this->assertDatabaseHas('addresses', ['label' => 'Kantor', 'is_default' => 0]);
    }

    public function test_address_fields_are_validated(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post(route('addresses.store'), [])
            ->assertSessionHasErrors(['label', 'recipient_name', 'phone', 'address']);

        $this->actingAs($customer)
            ->post(route('addresses.store'), $this->data(['phone' => 'bukan-nomor']))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_customer_can_update_own_address(): void
    {
        $customer = $this->customer();
        $address = $this->addressFor($customer);

        $this->actingAs($customer)
            ->put(route('addresses.update', $address), $this->data(['label' => 'Kantor', 'address' => 'Jl. Baru No. 5']))
            ->assertRedirect(route('addresses.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'label' => 'Kantor',
            'address' => 'Jl. Baru No. 5',
        ]);
    }

    public function test_customer_cannot_update_another_users_address(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $address = $this->addressFor($owner);

        $this->actingAs($intruder)
            ->put(route('addresses.update', $address), $this->data(['label' => 'Diubah']))
            ->assertForbidden();

        $this->assertSame('Rumah', $address->fresh()->label);
    }

    public function test_customer_can_delete_own_address(): void
    {
        $customer = $this->customer();
        $address = $this->addressFor($customer);

        $this->actingAs($customer)
            ->delete(route('addresses.destroy', $address))
            ->assertRedirect(route('addresses.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_customer_cannot_delete_another_users_address(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $address = $this->addressFor($owner);

        $this->actingAs($intruder)
            ->delete(route('addresses.destroy', $address))
            ->assertForbidden();

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_deleting_default_address_promotes_another_address(): void
    {
        $customer = $this->customer();
        $default = $this->addressFor($customer, ['label' => 'Rumah', 'is_default' => true]);
        $other = $this->addressFor($customer, ['label' => 'Kantor']);

        $this->actingAs($customer)->delete(route('addresses.destroy', $default));

        $this->assertTrue($other->fresh()->is_default);
        $this->assertDatabaseCount('addresses', 1);
    }

    public function test_setting_default_unsets_previous_default(): void
    {
        $customer = $this->customer();
        $first = $this->addressFor($customer, ['label' => 'Rumah', 'is_default' => true]);
        $second = $this->addressFor($customer, ['label' => 'Kantor']);

        $this->actingAs($customer)
            ->patch(route('addresses.default', $second))
            ->assertRedirect(route('addresses.index'))
            ->assertSessionHas('success');

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame(1, $customer->addresses()->where('is_default', true)->count());
    }

    public function test_customer_cannot_set_another_users_address_as_default(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer(['name' => 'Ripski']);
        $address = $this->addressFor($owner);

        $this->actingAs($intruder)
            ->patch(route('addresses.default', $address))
            ->assertForbidden();

        $this->assertFalse($address->fresh()->is_default);
    }
}
