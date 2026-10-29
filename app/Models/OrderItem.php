<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'product_name',
    'price',
    'quantity',
    'subtotal',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /** Item ini bagian dari satu pesanan. */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Relasi ke produk asli (nama/harga di tabel ini sudah di-snapshot). */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
