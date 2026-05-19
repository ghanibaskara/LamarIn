# LamarIn — REST API Sistem Informasi Lowongan Pekerjaan

> Tugas Akhir Mata Kuliah **Teknologi Integrasi Sistem - B**  
> Universitas Brawijaya · 2026

---

##  Deskripsi Proyek

**LamarIn** adalah REST API berbasis Laravel 13 yang dirancang sebagai sistem informasi lowongan pekerjaan. API ini memungkinkan penyedia kerja untuk memposting dan mengelola lowongan, serta memungkinkan pelamar untuk mencari, menyaring, dan melamar pada lowongan yang tersedia.

---

##  Tim Pengembang

| Nama | NIM | Fitur |
|------|-----|-------|
| Zulfikar Ramzy | 245150701111002 | Manajemen Lowongan (5.2) |
| Ghani Baskara Syah | 245150700111008 | Fitur Pelamaran (5.3) |
| Septian Nuril Arifin | 245150700111011 | Manajemen Pelamar oleh Penyedia (5.4) |
| Husein Sidharta Muhammad | 245150707111040 | Pelacakan Status Lamaran (5.5) |
| Ahmad Ahza Ainurrahman | 245150707111007 | Kategori & Filter Lowongan (5.6) |

---

##  Tech Stack

| Komponen | Versi |
|----------|-------|
| PHP | 8.3.x |
| Laravel | 13.x |
| Autentikasi | `tymon/jwt-auth` ^2.3 |
| Database | MySQL (via XAMPP / Laragon) |
| Dokumentasi API | `darkaonline/l5-swagger` (OpenAPI 3.0) |

---

##  Cara Menjalankan Proyek

### Prasyarat
- PHP **8.3.x** (wajib — jangan PHP 8.4+ karena ada bug Symfony)
- Composer
- MySQL (via XAMPP atau Laragon)
- Git

### Langkah Setup

```bash
# 1. Clone repositori
git clone https://github.com/ghanibaskara/LamarIn.git
cd LamarIn

# 2. Install dependensi
composer install --ignore-platform-reqs

# 3. Salin file environment
cp .env.example .env

# 4. Generate key aplikasi
php artisan key:generate

# 5. Generate JWT secret
php artisan jwt:secret
```

### Konfigurasi Database

Buka file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lamarin
DB_USERNAME=root
DB_PASSWORD=
```

### Migrasi & Seeder

```bash
# Jalankan migrasi database
php artisan migrate

# (Opsional) Isi data awal
php artisan db:seed

# Buat symlink storage (untuk file upload CV)
php artisan storage:link
```

### Menjalankan Server

```bash
php artisan serve
```

Server akan berjalan di `http://127.0.0.1:8000`.

---

## 📖 Dokumentasi API (Swagger)

Setelah server berjalan, buka dokumentasi interaktif di:

 **http://127.0.0.1:8000/api/documentation**

Untuk me-generate ulang dokumentasi setelah ada perubahan anotasi:

```bash
php artisan l5-swagger:generate
```

Tambahkan variabel berikut di `.env` agar HOST di Swagger sesuai:

```env
L5_SWAGGER_CONST_HOST=http://127.0.0.1:8000
```

---

##  Daftar Endpoint API

**Base URL:** `http://127.0.0.1:8000/api`

###  Auth
| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| POST | `/auth/register` | ❌ | Registrasi user baru |
| POST | `/auth/login` | ❌ | Login & dapatkan JWT token |
| GET | `/auth/me` | ✅ | Lihat data user yang login |
| POST | `/auth/refresh` | ✅ | Refresh JWT token |
| POST | `/auth/logout` | ✅ | Logout & invalidate token |

###  Kategori Pekerjaan
| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/kategori` | ❌ | Semua | Lihat semua kategori |
| POST | `/kategori` | ✅ | Penyedia | Tambah kategori baru |
| PUT | `/kategori/{id}` | ✅ | Penyedia | Update kategori |
| DELETE | `/kategori/{id}` | ✅ | Penyedia | Hapus kategori |

###  Lowongan Pekerjaan
| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/lowongan` | ✅ | Semua | Penyedia: lowongan milik sendiri · Pelamar: semua yang aktif |
| POST | `/lowongan` | ✅ | Penyedia | Buat lowongan baru |
| GET | `/lowongan/{id}` | ✅ | Semua | Lihat detail lowongan |
| PUT | `/lowongan/{id}` | ✅ | Penyedia | Update lowongan |
| DELETE | `/lowongan/{id}` | ✅ | Penyedia | Hapus lowongan |
| PATCH | `/lowongan/{id}/status` | ✅ | Penyedia | Toggle status aktif/nonaktif |

> Filter lowongan by kategori: `GET /lowongan?kategori=IT`

###  Lamaran
| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| POST | `/lamaran` | ✅ | Pelamar | Kirim lamaran (upload CV) |
| DELETE | `/lamaran/{id}` | ✅ | Pelamar | Batalkan lamaran (jika masih `menunggu`) |

###  Status Lamaran (Pelamar)
| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/lamaran/saya` | ✅ | Pelamar | Lihat semua riwayat lamaran |
| GET | `/lamaran/saya/{id}` | ✅ | Pelamar | Lihat detail & status satu lamaran |

###  Manajemen Pelamar (Penyedia)
| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| GET | `/lowongan/{id}/pelamar` | ✅ | Penyedia | Lihat semua pelamar pada satu lowongan |
| GET | `/lamaran/{id}` | ✅ | Penyedia | Lihat detail satu lamaran |
| PATCH | `/lamaran/{id}/status` | ✅ | Penyedia | Update status lamaran pelamar |

---

##  Format Request Penting

### Header yang Wajib

```
Accept: application/json
Authorization: Bearer <token>       ← untuk endpoint yang butuh auth
Content-Type: application/json      ← untuk request body JSON
Content-Type: multipart/form-data   ← untuk upload file (CV)
```

### Body Register

```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "pelamar",
  "nama_perusahaan": null,
  "telepon": "081234567890"
}
```

### Status Lamaran yang Valid

`menunggu` → `diproses` → `wawancara` → `diterima` / `ditolak`

---

##  Struktur Direktori

```
LamarIn/
├── app/Http/Controllers/Api/
│   ├── AuthController.php          # JWT Auth
│   ├── LowonganController.php      # Manajemen Lowongan
│   ├── LamaranController.php       # Fitur Pelamaran
│   ├── PelamarController.php       # Manajemen Pelamar oleh Penyedia
│   ├── StatusLamaranController.php # Pelacakan Status Lamaran
│   ├── KategoriController.php      # Kategori & Filter
│   └── SwaggerController.php       # Anotasi OpenAPI global
├── app/Models/
│   ├── User.php
│   ├── Lowongan.php
│   ├── Lamaran.php
│   └── KategoriPekerjaan.php
├── database/migrations/
├── routes/api.php
└── tests/Feature/
```

---

##  Menjalankan Test

```bash
php artisan test
```

---

##  Lisensi

Proyek ini dibuat untuk keperluan akademik. Hak cipta © 2026 Tim LamarIn - Universitas Brawijaya.
