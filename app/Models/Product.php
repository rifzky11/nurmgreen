<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'price',
    'stock',
    'image',
    'is_active',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** Setiap produk milik satu kategori. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope untuk halaman katalog pengunjung
    |--------------------------------------------------------------------------
    | Dipanggil tanpa awalan "scope", mis. Product::active()->search($q).
    | Semua nilai user tetap dikirim sebagai binding PDO, bukan disisipkan
    | ke string SQL, jadi tidak bisa dipakai untuk SQL injection.
    */

    /** Hanya produk yang tayang di katalog (is_active = true). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Pencarian nama produk.
     * Karakter wildcard LIKE milik user (% _) di-escape agar dicari
     * sebagai karakter biasa, bukan sebagai wildcard.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);

        return $query->where('name', 'like', '%'.$escaped.'%');
    }

    /** Filter satu kategori (terima id atau model Category). */
    public function scopeInCategory(Builder $query, int|Category|null $category): Builder
    {
        if ($category === null) {
            return $query;
        }

        return $query->where('category_id', $category instanceof Category ? $category->id : $category);
    }

    /**
     * Urutan katalog dari query string ?sort=.
     * Mapping pakai whitelist: nilai di luar daftar jatuh ke urutan default
     * (terbaru) sehingga kolom/ordering tidak bisa dipilih bebas oleh user.
     */
    public function scopeSorted(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest(),
        };
    }
}
