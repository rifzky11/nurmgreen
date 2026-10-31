<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
        ];
    }

    /** role bukan mass-assignable; default customer diisi eksplisit. */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            if (! $user->role) {
                $user->forceFill(['role' => 'customer']);
            }
        });
    }

    /** role bukan mass-assignable, jadi diset eksplisit setelah create. */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->forceFill(['role' => 'admin'])->save();
        });
    }
}
