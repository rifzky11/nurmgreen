<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi akun awal (admin + customer) untuk demo/UAS.
     *
     * updateOrCreate dipakai agar seeder aman dijalankan berulang:
     * kalau email sudah ada, data & password-nya diperbarui (bukan error duplikat).
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin NurmGreen',
                'email' => 'admin@nurmgreen.app',
                'password' => 'admin1234',
                'role' => 'admin',
            ],
            [
                'name' => 'Marl',
                'email' => 'marl@nurmgreen.app',
                'password' => 'marl1234',
                'role' => 'customer',
            ],
            [
                'name' => 'Ripski',
                'email' => 'ripski@nurmgreen.app',
                'password' => 'ripski1234',
                'role' => 'customer',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']], // kunci pencarian
                [
                    'name' => $data['name'],
                    // Password plaintext hanya ada di seeder, yang disimpan tetap hash
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                ]
            );
        }
    }
}
