<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bukti pembayaran manual (transfer bank) yang diunggah customer.
     * File disimpan di disk privat; kolom ini hanya menyimpan path relatif.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('notes');
            $table->timestamp('payment_proof_uploaded_at')->nullable()->after('payment_proof');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_proof', 'payment_proof_uploaded_at']);
        });
    }
};
