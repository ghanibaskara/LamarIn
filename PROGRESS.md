# PROGRESS.md — LamarIn REST API

> Dokumen ini mencatat progres pengerjaan project LamarIn secara kronologis.
> Update dokumen ini setiap kali fitur baru selesai.

---

## Sesi 1 — Setup Awal & Fitur Dasar (Zulfikar Ramzy)

**Tanggal:** 2026-05-16

### Yang dikerjakan
- Inisialisasi project Laravel 13.9.0 + `tymon/jwt-auth` ^2.3
- Setup konfigurasi JWT (`JWT_SECRET` di `.env`)
- Implementasi JWT Auth lengkap (`AuthController`)
- Migration `users` table + migration tambah kolom `role`, `nama_perusahaan`, `telepon`
- Migration `lowongans` table
- Model `Lowongan.php` dengan relasi dan fillable
- `LowonganController.php` — CRUD lengkap + `updateStatus()` + Swagger `@OA\` annotation
- `routes/api.php` — routing auth + lowongan

### Status awal fitur
| Fitur | Status |
|-------|--------|
| JWT Auth (register, login, logout, me, refresh) | ✅ Fungsional (ada bug) |
| Manajemen Lowongan CRUD | ✅ Fungsional (ada bug) |

---

## Sesi 2 — Bug Fixes + Fitur Pelamaran (Ghani Baskara Syah)

**Tanggal:** 2026-05-17

### Bug yang Diperbaiki

#### Bug 1 — `User::$fillable` tidak mencakup kolom `role`, `nama_perusahaan`, `telepon`
- **File:** `app/Models/User.php`
- **Masalah:** Model menggunakan `#[Fillable(['name', 'email', 'password'])]` (PHP attribute) yang tidak mencakup kolom yang ditambahkan di migration kedua. Semua user otomatis jadi `pelamar` apapun role yang dikirim.
- **Perbaikan:** Ganti ke `protected $fillable = [...]` yang mencakup semua kolom. Tambahkan relasi `lowongans()` dan `lamarans()`.

#### Bug 2 — `AuthController::register()` tidak memvalidasi/mengisi `role`, `nama_perusahaan`, `telepon`
- **File:** `app/Http/Controllers/Api/AuthController.php`
- **Masalah:** Field `role`, `nama_perusahaan`, `telepon` tidak masuk validasi maupun `User::create()`. Tidak mungkin mendaftar sebagai penyedia.
- **Perbaikan:** Tambahkan validasi `role` (opsional, default `pelamar`), `nama_perusahaan` (`required_if:role,penyedia`), dan `telepon` (nullable).

#### Bug 3 — `LowonganController` tidak ada pengecekan role `penyedia`
- **File:** `app/Http/Controllers/Api/LowonganController.php`
- **Masalah:** `store()`, `update()`, `destroy()`, `updateStatus()` tidak memeriksa role. Pelamar bisa membuat/mengubah/menghapus lowongan.
- **Perbaikan:** Tambahkan pengecekan `Auth::user()->role !== 'penyedia'` → 403 di awal method.

#### Bug 4 (Logika) — `index()` dan `show()` tidak membedakan role
- **File:** `app/Http/Controllers/Api/LowonganController.php`
- **Masalah:** `index()` hanya menampilkan lowongan milik user saat ini (hanya relevan untuk penyedia). `show()` menolak akses siapapun yang bukan owner, padahal pelamar juga perlu melihat detail lowongan.
- **Perbaikan:**
  - `index()`: jika `penyedia` → tampilkan milik sendiri; jika `pelamar` → tampilkan semua lowongan `aktif`.
  - `show()`: jika `penyedia` → cek ownership; jika `pelamar` → cek `status = aktif`.

---

### Fitur Baru — 5.3 Fitur Pelamaran (Ghani Baskara Syah)

#### File yang dibuat

| File | Keterangan |
|------|------------|
| `database/migrations/2026_05_16_000003_create_lamarans_table.php` | Migration tabel `lamarans` |
| `app/Models/Lamaran.php` | Model dengan relasi `pelamar` (User) dan `lowongan` |
| `app/Http/Controllers/Api/LamaranController.php` | Controller store + destroy |
| `routes/api.php` | Update tambahkan route `/api/lamaran` |

#### Endpoint baru

| Method | Endpoint | Role | Deskripsi |
|--------|----------|------|-----------|
| POST | `/api/lamaran` | pelamar | Mendaftar lowongan + upload CV |
| DELETE | `/api/lamaran/{id}` | pelamar | Batalkan lamaran (hanya status `menunggu`) |

#### Logika penting `store()`
- Role check: hanya `pelamar` yang boleh melamar
- Cek lowongan `status = aktif`
- Cek tidak double apply (query check sebelum insert)
- Upload CV wajib ke `Storage::disk('public')` path `cv/{user_id}_{timestamp}.ext`
- Upload surat lamaran opsional ke path `surat/{user_id}_{timestamp}.ext`
- Status otomatis `menunggu`, tidak bisa di-set oleh pelamar

#### Logika penting `destroy()`
- Role check: hanya `pelamar`
- Ownership check: `lamaran.user_id === Auth::id()`
- Status guard: hanya bisa batalkan jika `status = menunggu`
- Hapus file CV dan surat dari storage sebelum delete record

---

### File Testing yang dibuat

| File | Jumlah Tests |
|------|-------------|
| `tests/TestCase.php` | Helper: `getTokenForUser()`, `authHeaders()`, `makePenyedia()`, `makePelamar()` |
| `tests/Feature/AuthTest.php` | 10 tests |
| `tests/Feature/LowonganTest.php` | 18 tests |
| `tests/Feature/LamaranTest.php` | 12 tests (+ 5 dari ExampleTest) |

**Total: 45 tests, 101 assertions — semua PASSED ✅**

---

## Sesi 3 — Fitur 5.4 Manajemen Pelamar oleh Penyedia (Septian Nuril Arifin)

**Tanggal:** 2026-05-17

### Bug yang Diperbaiki

#### Bug 5 — `JWT_SECRET` dan `APP_KEY` tidak ada di `phpunit.xml`
- **File:** `phpunit.xml`
- **Masalah:** Test environment tidak memiliki `JWT_SECRET` sehingga seluruh test yang menggunakan JWT auth gagal dengan error `Secret is not set`. `APP_KEY` juga tidak ada sehingga `ExampleTest` gagal.
- **Perbaikan:** Tambahkan `JWT_SECRET` (test key) dan `APP_KEY` (generate via `php artisan key:generate`) ke blok `<php>` di `phpunit.xml`.

### Fitur Baru — 5.4 Manajemen Pelamar oleh Penyedia

#### File yang dibuat

| File | Keterangan |
|------|------------|
| `app/Http/Controllers/Api/PelamarController.php` | Controller `index` + `show` + `updateStatus` |
| `tests/Feature/PelamarTest.php` | 16 feature tests |

#### File yang dimodifikasi

| File | Perubahan |
|------|-----------|
| `routes/api.php` | Tambah 3 route baru + import `PelamarController` |
| `phpunit.xml` | Tambah `JWT_SECRET` dan `APP_KEY` untuk test environment |

#### Endpoint baru

| Method | Endpoint | Role | Deskripsi |
|--------|----------|------|-----------|
| GET | `/api/lowongan/{id}/pelamar` | penyedia | Semua pelamar pada lowongan milik penyedia |
| GET | `/api/lamaran/{id}` | penyedia | Detail satu lamaran (cek ownership via lowongan) |
| PATCH | `/api/lamaran/{id}/status` | penyedia | Update status lamaran + catatan opsional |

#### Logika penting `index()`
- Role check: hanya `penyedia`
- Ownership check: lowongan harus milik penyedia yang login (`lowongan.user_id === Auth::id()`)
- Eager load relasi `pelamar` (User) pada setiap lamaran

#### Logika penting `show()`
- Role check: hanya `penyedia`
- Ownership check: `lamaran.lowongan.user_id === Auth::id()`
- Eager load `pelamar` dan `lowongan`

#### Logika penting `updateStatus()`
- Role check: hanya `penyedia`
- Validasi `status` hanya boleh: `menunggu`, `diproses`, `wawancara`, `diterima`, `ditolak`
- Field `catatan_penyedia` opsional (nullable string)
- Ownership check: lowongan yang bersangkutan harus milik penyedia yang login
- Response include `pelamar` dan `lowongan` setelah update

#### Catatan penting untuk Husein (5.5)
Route `/lamaran/saya` **WAJIB** didaftarkan **SEBELUM** `/lamaran/{id}` di `routes/api.php` agar Laravel tidak memperlakukan string `"saya"` sebagai `{id}`.

---

### File Testing yang dibuat

| File | Jumlah Tests |
|------|-------------|
| `tests/Feature/PelamarTest.php` | 16 tests |

**Total keseluruhan: 61 tests, 130 assertions — semua PASSED ✅**

#### Konfigurasi test
- Database: SQLite in-memory (dari `phpunit.xml`)
- File upload: `Storage::fake('public')` + `UploadedFile::fake()`
- JWT token: diambil via `auth('api')->login($user)` langsung

---

## Status Fitur Saat Ini

| No | Fitur | Anggota | Status |
|----|-------|---------|--------|
| 5.1 | JWT Auth + Role Register | Semua | ✅ Selesai & Tested |
| 5.2 | Manajemen Lowongan (CRUD) | Zulfikar | ✅ Selesai & Tested |
| 5.3 | Fitur Pelamaran | Ghani | ✅ Selesai & Tested |
| 5.4 | Manajemen Pelamar oleh Penyedia | Septian | ✅ Selesai & Tested |
| 5.5 | Pelacakan Status Lamaran | Husein | 🔲 Belum |
| 5.6 | Kategori & Filter Lowongan | Ahza | 🔲 Belum |
| — | Swagger/OpenAPI Docs | Semua | ⏳ Annotation sudah di LowonganController, package belum install |

---

## Catatan Teknis

- **PHP:** Wajib 8.3.x — jangan 8.4/8.5 (ada bug Symfony)
- **DB Development:** MySQL via XAMPP — ubah `.env` ke MySQL
- **DB Testing:** SQLite in-memory (otomatis via `phpunit.xml`)
- **Storage:** Jalankan `php artisan storage:link` untuk akses file upload via URL
- **Saat ada error Symfony setelah pull:** `composer update --ignore-platform-reqs -W`
