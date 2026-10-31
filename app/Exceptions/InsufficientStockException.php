<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat checkout menemukan stok tidak cukup / produk tidak tersedia.
 * Ditangkap di CheckoutController untuk menampilkan pesan error ke user.
 */
class InsufficientStockException extends RuntimeException {}
