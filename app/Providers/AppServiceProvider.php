<?php

namespace App\Providers;

use App\Models\Order;
use App\Policies\OrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // InfinityFree memakai MySQL/MariaDB lama dengan batas index 767 byte;
        // batasi panjang default string agar unique index utf8mb4 tidak gagal.
        Schema::defaultStringLength(191);

        // Daftarkan OrderPolicy secara eksplisit untuk model Order.
        Gate::policy(Order::class, OrderPolicy::class);
    }
}
