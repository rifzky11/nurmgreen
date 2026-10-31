<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * User hanya boleh melihat pesanan miliknya sendiri.
     * Dipanggil lewat $this->authorize('view', $order) di OrderController.
     */
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    /**
     * Pembatalan hanya untuk pemilik pesanan DAN status masih pending.
     * Pesanan yang sudah dibayar/dikirim/selesai/dibatalkan tidak boleh
     * dibatalkan (stok hanya dikembalikan sekali saat pending).
     */
    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->status === 'pending';
    }
}
