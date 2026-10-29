<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'product_id', 'quantity'])]
class CartItem extends Model
{
    /** Item keranjang milik satu user. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Item merujuk ke satu produk. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
