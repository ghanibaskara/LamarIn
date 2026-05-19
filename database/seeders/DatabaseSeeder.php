<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Lowongan;
use App\Models\KategoriPekerjaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Amankan Tabel Kategori Pekerjaan
        if (!Schema::hasTable('kategori_pekerjaans')) {
            Schema::create('kategori_pekerjaans', function (Blueprint $table) {
                $table->id();
                $table->string('nama_kategori', 100)->unique();
                $table->text('deskripsi')->nullable();
                $table->timestamps();
            });
        }

        // 2. Buat Akun Contoh untuk Pelamar
        $pelamar = User::create([
            'name' => 'Pejuang Karir',
            'email' => 'pelamar@lamarin.com',
            'password' => Hash::make('password123'),
            'role' => 'pelamar',
        ]);

        // 3. Buat Akun Contoh untuk Penyedia Kerja (Perusahaan)
        $penyedia = User::create([
            'name' => 'PT Solusi Integrasi Teknologi',
            'email' => 'hrd@solusitekno.com',
            'password' => Hash::make('password123'),
            'role' => 'penyedia',
        ]);

        // 4. Buat Data Kategori Pekerjaan
        $kategoriIT = KategoriPekerjaan::create([
            'nama_kategori' => 'Teknologi Informasi',
            'deskripsi' => 'Bidang rekayasa perangkat lunak, jaringan, keamanan, dan AI.'
        ]);

        $kategoriDesain = KategoriPekerjaan::create([
            'nama_kategori' => 'Desain & Kreatif',
            'deskripsi' => 'Bidang UI/UX, desain grafis, editing video, dan animasi.'
        ]);

        // 5. Buat Data Lowongan (Telah disesuaikan nama kolomnya)
        Lowongan::create([
            'user_id' => $penyedia->id, 
            'kategori_id' => $kategoriIT->id,
            'judul' => 'Junior Full-Stack Developer (Laravel & Vue.js)',
            'deskripsi' => 'Dibutuhkan pengembang web yang memahami framework Laravel, Vue.js, dan pengelolaan database MySQL/MongoDB.',
            'kualifikasi' => '1. Mahir PHP & JavaScript. 2. Paham arsitektur REST API. 3. Mampu bekerja dalam tim proyek.',
            'lokasi' => 'Malang (Hybrid)',
            'jenis_pekerjaan' => 'full-time',
            'gaji_min' => 4500000,
            'gaji_max' => 7000000,
            'batas_daftar' => '2024-12-31',
            'status' => 'aktif'
        ]);

        Lowongan::create([
            'user_id' => $penyedia->id,
            'kategori_id' => $kategoriDesain->id,
            'judul' => 'UI/UX Designer',
            'deskripsi' => 'Mendesain antarmuka aplikasi mobile dan aset grafis kreatif untuk kebutuhan produk digital.',
            'kualifikasi' => '1. Menguasai Figma / Adobe XD. 2. Memiliki portfolio desain UI/UX yang menarik.',
            'lokasi' => 'Bantul, Yogyakarta (Remote)',
            'jenis_pekerjaan' => 'full-time',
            'gaji_min' => 4000000,
            'gaji_max' => 7000000,
            'batas_daftar' => '2024-12-31',
            'status' => 'aktif'
        ]);
    }
}