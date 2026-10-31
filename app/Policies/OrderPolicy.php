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

    /**
     * Unggah bukti pembayaran hanya untuk pemilik pesanan yang masih pending.
     * Admin tidak mengunggah bukti, hanya memverifikasi.
     */
    public function uploadProof(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->status === 'pending';
    }

    /**
     * Melihat file bukti pembayaran: pemilik pesanan atau admin.
     * Dipakai route streaming agar file di disk privat tidak bisa diakses
     * langsung oleh customer lain.
     */
    public function viewPaymentProof(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    /**
     * Konfirmasi "Sudah Diterima" hanya untuk PEMILIK pesanan dan hanya
     * saat status "shipped". Admin tidak boleh menandai completed.
     */
    public function markReceived(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->status === 'shipped';
    }
}
