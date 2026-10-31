<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'label',
    'recipient_name',
    'phone',
    'address',
    'is_default',
])]
class Address extends Model
{
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /** Alamat milik satu customer. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
