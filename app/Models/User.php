<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'address'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            // Password selalu di-hash saat disimpan
            'password' => 'hashed',
        ];
    }

    /** Satu user punya banyak item keranjang. */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** Satu user punya banyak alamat tersimpan (buku alamat). */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /** Satu user punya banyak pesanan. */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
