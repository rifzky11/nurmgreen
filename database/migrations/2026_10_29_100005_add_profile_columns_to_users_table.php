<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyesuaikan tabel users lama.
     *
     * Kolom role/phone/address sebenarnya sudah ditulis di create_users_table,
     * tetapi database yang dibuat sebelum file itu diubah bisa belum memilikinya.
     * Pengecekan hasColumn membuat migrasi ini aman dijalankan di database baru
     * maupun lama (tidak error "duplicate column").
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'customer'])->default('customer')->after('password');
            });
        }

        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('role');
            });
        }

        if (! Schema::hasColumn('users', 'address')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('address')->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        // Hapus hanya jika kolomnya memang ada.
        foreach (['address', 'phone', 'role'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
