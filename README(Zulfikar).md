# LamarIn - Sistem Informasi Lowongan Pekerjaan Berbasis REST API

## Deskripsi
LamarIn adalah sistem informasi lowongan pekerjaan berbasis REST API yang dibangun menggunakan Laravel 13 + JWT Auth. Project ini adalah tugas akhir mata kuliah Teknologi Integrasi Sistem - B, Universitas Brawijaya 2026.

## Anggota Tim
| No | Nama | NIM | Fitur |
|----|------|-----|-------|
| 1 | Zulfikar Ramzy | 245150701111002 | Manajemen Lowongan Pekerjaan (CRUD) ✅ |
| 2 | Ghani Baskara Syah | 245150700111008 | Fitur Pelamaran (Pelamar mendaftar lowongan) 🔲 |
| 3 | Septian Nuril Arifin | 245150700111011 | Manajemen Pelamar oleh Penyedia 🔲 |
| 4 | Husein Sidharta Muhammad | 245150707111040 | Pelacakan Status Lamaran Real-time 🔲 |
| 5 | Ahmad Ahza Ainurrahman | 245150707111007 | Kategori & Filter Lowongan 🔲 |
| Semua | - | - | JWT Auth & Swagger Docs ✅ (5.1 selesai) |

---

## Tech Stack
- **Framework:** Laravel 13.9.0
- **PHP:** 8.3.x (WAJIB pakai 8.3, jangan 8.4/8.5 — ada bug Symfony)
- **Database:** MySQL (XAMPP)
- **Auth:** JWT via `tymon/jwt-auth` 2.3.0
- **Docs:** L5-Swagger (belum diimplementasi)

---

## Setup & Install

### Requirement
- PHP 8.3.x (Thread Safe x64)
- Composer
- XAMPP (MySQL)
- Git

### Langkah Install
```bash
git clone <repo-url>
cd LamarIn
composer install --ignore-platform-reqs
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### Konfigurasi `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lamarin
DB_USERNAME=root
DB_PASSWORD=
```

### Jalankan Migration
```bash
php artisan migrate
```

### Jalankan Server
```bash
php artisan serve
```

### ⚠️ Catatan Penting
- Jika error Symfony setelah pull, jalankan: `composer update --ignore-platform-reqs -W`
- Selalu pakai header `Accept: application/json` dan `Authorization: Bearer <token>` di semua request
- Tabel `kategori_pekerjaans` belum ada — migration ada di bagian Ahza (5.6). Foreign key `kategori_id` di `lowongans` sementara tidak pakai constraint

---

## Struktur Database

### Tabel yang sudah ada ✅
- `users` — dengan kolom tambahan: `role` (enum: penyedia/pelamar), `nama_perusahaan`, `telepon`
- `lowongans` — tabel utama lowongan pekerjaan

### Tabel yang belum ada 🔲
- `lamarans` — akan dibuat oleh Ghani (5.3)
- `kategori_pekerjaans` — akan dibuat oleh Ahza (5.6)

---

## Endpoint API

### 5.1 Auth [Semua Anggota] ✅
| Method | Endpoint | Deskripsi | Auth |
|--------|----------|-----------|------|
| POST | `/api/auth/register` | Registrasi user baru | ❌ |
| POST | `/api/auth/login` | Login & dapat token JWT | ❌ |
| POST | `/api/auth/logout` | Logout | ✅ |
| GET | `/api/auth/me` | Lihat profil user login | ✅ |
| POST | `/api/auth/refresh` | Refresh token | ✅ |

### 5.2 Manajemen Lowongan [Zulfikar Ramzy] ✅
| Method | Endpoint | Deskripsi | Role |
|--------|----------|-----------|------|
| GET | `/api/lowongan` | Lihat semua lowongan milik penyedia | penyedia |
| POST | `/api/lowongan` | Buat lowongan baru | penyedia |
| GET | `/api/lowongan/{id}` | Lihat detail satu lowongan | penyedia |
| PUT | `/api/lowongan/{id}` | Update lowongan | penyedia |
| DELETE | `/api/lowongan/{id}` | Hapus lowongan | penyedia |
| PATCH | `/api/lowongan/{id}/status` | Ubah status aktif/nonaktif | penyedia |

### 5.3 Fitur Pelamaran [Ghani Baskara Syah] 🔲
| Method | Endpoint | Deskripsi | Role |
|--------|----------|-----------|------|
| GET | `/api/lowongan` | Lihat semua lowongan aktif | pelamar |
| GET | `/api/lowongan/{id}` | Lihat detail lowongan | pelamar |
| POST | `/api/lamaran` | Daftar pada lowongan | pelamar |
| DELETE | `/api/lamaran/{id}` | Batalkan lamaran (status: menunggu) | pelamar |

### 5.4 Manajemen Pelamar [Septian Nuril Arifin] 🔲
| Method | Endpoint | Deskripsi | Role |
|--------|----------|-----------|------|
| GET | `/api/lowongan/{id}/pelamar` | Lihat semua pelamar pada lowongan | penyedia |
| GET | `/api/lamaran/{id}` | Lihat detail lamaran | penyedia |
| PATCH | `/api/lamaran/{id}/status` | Update status lamaran | penyedia |

### 5.5 Pelacakan Status [Husein Sidharta Muhammad] 🔲
| Method | Endpoint | Deskripsi | Role |
|--------|----------|-----------|------|
| GET | `/api/lamaran/saya` | Lihat semua lamaran saya | pelamar |
| GET | `/api/lamaran/saya/{id}` | Lihat detail & riwayat status | pelamar |

### 5.6 Kategori & Filter [Ahmad Ahza Ainurrahman] 🔲
| Method | Endpoint | Deskripsi | Role |
|--------|----------|-----------|------|
| GET | `/api/kategori` | Lihat semua kategori | semua |
| POST | `/api/kategori` | Tambah kategori baru | penyedia |
| PUT | `/api/kategori/{id}` | Update kategori | penyedia |
| DELETE | `/api/kategori/{id}` | Hapus kategori | penyedia |
| GET | `/api/lowongan?kategori=IT` | Filter lowongan by kategori | semua |

---

## File yang Sudah Dibuat (Bagian Zulfikar)

```
app/
  Models/
    Lowongan.php                          ✅
database/
  migrations/
    2026_05_16_000001_create_lowongans_table.php          ✅
    2026_05_16_000002_add_role_to_users_table.php         ✅
app/
  Http/
    Controllers/
      Api/
        LowonganController.php            ✅
routes/
  api.php                                 ✅ (updated)
```

---

## TO DO - 5.3 Fitur Pelamaran [Ghani Baskara Syah]

### Yang perlu dibuat:

#### 1. Migration `lamarans`
File: `database/migrations/2026_05_16_000003_create_lamarans_table.php`
```php
Schema::create('lamarans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('lowongan_id')->constrained('lowongans')->onDelete('cascade');
    $table->string('cv_path');
    $table->string('surat_lamaran_path')->nullable();
    $table->enum('status', ['menunggu', 'diproses', 'wawancara', 'diterima', 'ditolak'])->default('menunggu');
    $table->text('catatan_penyedia')->nullable();
    $table->timestamps();
    $table->unique(['user_id', 'lowongan_id']); // cegah double apply
});
```

#### 2. Model `Lamaran.php`
- `belongsTo` User (pelamar)
- `belongsTo` Lowongan

#### 3. Controller `LamaranController.php`
- `index()` — GET `/api/lowongan` hanya tampilkan status = **aktif** (beda dengan Zulfikar yang tampilkan semua milik penyedia)
- `show()` — GET `/api/lowongan/{id}` detail lowongan aktif
- `store()` — POST `/api/lamaran` daftar lowongan, cek role = pelamar, cek tidak double apply, upload CV
- `destroy()` — DELETE `/api/lamaran/{id}` batalkan lamaran, hanya jika status = **menunggu**

#### 4. Update `routes/api.php`
Tambahkan di dalam `Route::middleware('auth:api')`:
```php
Route::post('/lamaran', [LamaranController::class, 'store']);
Route::delete('/lamaran/{id}', [LamaranController::class, 'destroy']);
```

### Catatan untuk Ghani:
- GET `/api/lowongan` BERBEDA dengan milik Zulfikar — punya Ghani filter `status = aktif` dan bisa diakses pelamar
- Perlu handle **file upload CV** (cv_path) — gunakan `Storage::disk('public')`
- Cek `Auth::user()->role === 'pelamar'` sebelum boleh melamar
- UNIQUE constraint `(user_id, lowongan_id)` sudah ada di migration — tangkap exception jika double apply
- Jalankan `php artisan migrate` setelah buat migration baru
