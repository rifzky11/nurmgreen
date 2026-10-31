<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kesalahan yang wajar saat checkout (mis. stok kurang, item tidak ditemukan).
 * Ditangkap CheckoutController untuk mengembalikan user ke form dengan pesan.
 */
class CheckoutException extends RuntimeException {}
