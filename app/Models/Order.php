<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'order_code',
    'total_price',
    'status',
    'shipping_name',
    'shipping_phone',
    'shipping_address',
    'notes',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
        ];
    }

    /** Pesanan milik satu customer. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Satu pesanan punya banyak baris item. */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Label status berbahasa Indonesia untuk ditampilkan di view. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Dibayar',
            'shipped' => 'Dikirim',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    /** Hanya pesanan pending yang boleh dibatalkan customer. */
    public function canBeCancelled(): bool
    {
        return $this->status === 'pending';
    }
}
