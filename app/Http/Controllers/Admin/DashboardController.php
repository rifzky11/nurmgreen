<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Halaman dashboard admin (masih placeholder).
     * Akses sudah dijaga middleware role:admin di routes/web.php.
     */
    public function index(): View
    {
        return view('admin.dashboard');
    }
}
