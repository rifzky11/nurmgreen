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
    'payment_proof',
    'payment_proof_uploaded_at',
    'shipped_at',
    'completed_at',
])]
class Order extends Model
{
    /** Semua status yang dikenal sistem, sesuai alur utama pesanan. */
    public const STATUSES = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'payment_proof_uploaded_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
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

    /**
     * Peta transisi status yang diizinkan (state machine pesanan).
     *
     * Alur utama: pending -> paid -> shipped -> completed.
     * Admin hanya boleh sampai "shipped"; status "completed" hanya bisa
     * diubah oleh buyer lewat endpoint markReceived. Pembatalan hanya
     * sebelum barang dikirim (pending/paid).
     */
    public static function allowedTransitions(): array
    {
        return [
            'pending' => ['paid', 'cancelled'],
            'paid' => ['shipped', 'cancelled'],
            'shipped' => [],
            'completed' => [],
            'cancelled' => [],
        ];
    }

    /** Label status berbahasa Indonesia. */
    public static function statusLabels(): array
    {
        return [
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Dibayar',
            'shipped' => 'Sedang Dikirim',
            'completed' => 'Sudah Diterima',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /** Token warna badge status, dipakai seragam di semua view. */
    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'paid' => 'info',
            'shipped' => 'primary',
            'completed' => 'success',
            'cancelled' => 'neutral',
            default => 'neutral',
        };
    }

    /** Status tujuan yang sah dari status saat ini. */
    public function nextStatuses(): array
    {
        return static::allowedTransitions()[$this->status] ?? [];
    }

    /** Apakah pesanan boleh berpindah ke status tujuan tertentu. */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /** Label status berbahasa Indonesia untuk ditampilkan di view. */
    public function statusLabel(): string
    {
        return static::statusLabels()[$this->status] ?? ucfirst($this->status);
    }

    /** Hanya pesanan pending yang boleh dibatalkan customer. */
    public function canBeCancelled(): bool
    {
        return $this->status === 'pending';
    }

    /** Buyer hanya boleh mengonfirmasi pesanan yang sedang dikirim. */
    public function canBeReceived(): bool
    {
        return $this->status === 'shipped';
    }

    /** Apakah customer sudah mengunggah bukti pembayaran. */
    public function hasPaymentProof(): bool
    {
        return ! is_null($this->payment_proof);
    }
}
