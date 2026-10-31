<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi akun awal (admin + customer) dan data katalog untuk demo/UAS.
     *
     * updateOrCreate dipakai agar seeder aman dijalankan berulang:
     * kalau email/slug sudah ada, datanya diperbarui (bukan error duplikat).
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->seedCatalog();
    }

    private function seedUsers(): void
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
            $user = User::updateOrCreate(
                ['email' => $data['email']], // kunci pencarian
                [
                    'name' => $data['name'],
                    // Password plaintext hanya ada di seeder, yang disimpan tetap hash
                    'password' => Hash::make($data['password']),
                ]
            );

            // role tidak mass-assignable, jadi di-set eksplisit.
            $user->forceFill(['role' => $data['role']])->save();
        }
    }

    /**
     * Kategori + produk contoh untuk halaman katalog.
     * Sengaja mencakup: stok habis (0), stok menipis, dan produk nonaktif
     * (tidak tayang) supaya perilaku katalog bisa diuji dari data.
     */
    private function seedCatalog(): void
    {
        $categories = [
            ['name' => 'Tanaman Hias', 'slug' => 'tanaman-hias', 'description' => 'Tanaman hias indoor dan outdoor untuk mempercantik ruangan.'],
            ['name' => 'Bibit & Benih', 'slug' => 'bibit-benih', 'description' => 'Bibit sayur dan benih unggul siap tanam.'],
            ['name' => 'Pupuk & Media', 'slug' => 'pupuk-media', 'description' => 'Pupuk organik, NPK, dan media tanam.'],
            ['name' => 'Obat Tanaman', 'slug' => 'obat-tanaman', 'description' => 'Fungisida dan pestisida nabati untuk merawat tanaman.'],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $kategori = fn (string $slug) => Category::where('slug', $slug)->value('id');

        // Salin gambar dummy dari public/images ke storage supaya kartu
        // produk punya visual tanpa perlu upload manual lewat admin.
        foreach (range(1, 6) as $i) {
            $target = "products/p{$i}.svg";

            if (! Storage::disk('public')->exists($target)) {
                Storage::disk('public')->put($target, file_get_contents(public_path("images/p{$i}.svg")));
            }
        }

        $products = [
            // [name, slug, kategori, harga, stok, aktif, gambar, deskripsi]
            ['Monstera Deliciosa pot 50cm tanaman hias indoor', 'monstera-deliciosa-50cm', 'tanaman-hias', 85000, 12, true, 'p1', 'Monstera deliciosa pot 50cm, daun lebar mengkilap. Perawatan mudah, cocok untuk ruang tamu maupun kantor.'],
            ['Lidah Mertua Sansevieria pot 30cm pembersih udara', 'lidah-mertua-sansevieria-30cm', 'tanaman-hias', 45000, 8, true, 'p6', 'Sansevieria standar 30cm, tahan minim air dan pembersih udara alami.'],
            ['Kaktus mini pot terracotta succulent campur', 'kaktus-mini-terracotta', 'tanaman-hias', 18500, 0, true, 'p2', 'Kaktus mini dalam pot terracotta 8cm. Varian succulent dikirim acak.'],
            ['Bibit pakcoy unggul siap tanam 50 batang', 'bibit-pakcoy-unggul-50', 'bibit-benih', 12000, 40, true, 'p4', 'Bibit pakcoy F1 berumur 14 hari, tinggi 8-10 cm, siap pindah tanam.'],
            ['Bibit tomat cherry pot polybag', 'bibit-tomat-cherry', 'bibit-benih', 9500, 25, true, 'p4', 'Bibit tomat cherry tinggi 15 cm, berbuah manis, cocok untuk pot rumah.'],
            ['Bibit cabai rawit merah unggul', 'bibit-cabai-rawit-merah', 'bibit-benih', 11000, 4, true, 'p4', 'Bibit cabai rawit merah F1, pedas khas, produktif untuk pekarangan.'],
            ['Pupuk kompos organik 5kg media tanam subur', 'pupuk-kompos-organik-5kg', 'pupuk-media', 32000, 30, true, 'p3', 'Kompos organik matang 5kg dari kotoran ternak, bebas bau, memperbaiki struktur tanah.'],
            ['Pupuk NPK Mutiara 1kg all purpose', 'pupuk-npk-mutiara-1kg', 'pupuk-media', 16500, 50, true, 'p3', 'NPK 16-16-16 untuk segala jenis tanaman, 1kg.'],
            ['Media tanam cocopeat 2kg serbuk halus', 'cocopeat-2kg', 'pupuk-media', 14000, 0, true, 'p3', 'Cocopeat serbuk halus 2kg, kapasitas air tinggi, campuran ideal media tanam.'],
            ['Fungisida nabati 250ml obat jamur daun', 'fungisida-nabati-250ml', 'obat-tanaman', 27900, 18, true, 'p5', 'Fungisida nabati aman untuk sayuran, mengatasi bercak daun dan embun tepung.'],
            ['Pestisida alami neem oil 100ml anti hama', 'neem-oil-100ml', 'obat-tanaman', 23000, 7, true, 'p5', 'Neem oil murni 100ml untuk kutu daun, thrips, dan tungau. Aman untuk tanaman dalam ruangan.'],
            // Nonaktif: tidak tayang di katalog (is_active = false)
            ['Tanaman indoor golden pothos gantung', 'golden-pothos-gantung', 'tanaman-hias', 35000, 5, false, 'p1', 'Golden pothos variegata untuk pot gantung, perawatan sangat mudah.'],
        ];

        foreach ($products as [$name, $slug, $catSlug, $price, $stock, $isActive, $image, $desc]) {
            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'category_id' => $kategori($catSlug),
                    'description' => $desc,
                    'price' => $price,
                    'stock' => $stock,
                    'image' => "products/{$image}.svg",
                    'is_active' => $isActive,
                ]
            );
        }
    }
}
