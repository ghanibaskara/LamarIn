# AGENT.md — LamarIn REST API

> Panduan teknis untuk AI agent maupun anggota tim yang mengerjakan project ini.
> Baca file ini **sebelum** membuat atau memodifikasi kode apapun.

---

## 1. Ikhtisar Project

**LamarIn** adalah REST API sistem informasi lowongan pekerjaan berbasis Laravel 13 + JWT Auth.
Dibangun sebagai tugas akhir mata kuliah **Teknologi Integrasi Sistem - B**, Universitas Brawijaya 2026.

- **Framework:** Laravel 13.9.0
- **PHP:** 8.3.x (**wajib 8.3**, jangan 8.4/8.5 — ada bug Symfony)
- **Database:** MySQL via XAMPP (lokal), namun `.env` default pakai SQLite — ubah ke MySQL saat develop
- **Auth:** `tymon/jwt-auth` ^2.3
- **Docs:** L5-Swagger via anotasi `@OA\` di controller
- **Base URL:** `http://localhost:8000/api`

---

## 2. Tim & Pembagian Fitur

| No | Nama | NIM | Fitur | Status |
|----|------|-----|-------|--------|
| 1 | Zulfikar Ramzy | 245150701111002 | Manajemen Lowongan (5.2) | ✅ Selesai & Tested |
| 2 | Ghani Baskara Syah | 245150700111008 | Fitur Pelamaran (5.3) | ✅ Selesai & Tested |
| 3 | Septian Nuril Arifin | 245150700111011 | Manajemen Pelamar oleh Penyedia (5.4) | 🔲 Belum |
| 4 | Husein Sidharta Muhammad | 245150707111040 | Pelacakan Status Lamaran (5.5) | ✅ Selesai & Tested |
| 5 | Ahmad Ahza Ainurrahman | 245150707111007 | Kategori & Filter Lowongan (5.6) | 🔲 Belum |
| Semua | — | — | JWT Auth (5.1) + Role Register | ✅ Selesai & Tested |
| Semua | — | — | Swagger/OpenAPI Docs | ⏳ Annotation ada, package belum install |

---

## 3. Struktur Direktori Penting

```
LamarIn/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php          ✅ (5.1)
│   │           ├── LowonganController.php      ✅ (5.2)
│   │           ├── LamaranController.php       ✅ (5.3)
│   │           ├── PelamarController.php       🔲 (5.4 - Septian)
│   │           ├── StatusLamaranController.php ✅ (5.5 - Husein)
│   │           └── KategoriController.php      🔲 (5.6 - Ahza)
│   └── Models/
│       ├── User.php              ✅ (fillable lengkap + relasi lowongans, lamarans)
│       ├── Lowongan.php          ✅
│       ├── Lamaran.php           ✅ (fillable + relasi pelamar, lowongan)
│       └── KategoriPekerjaan.php ⚠️ Stub kosong — perlu dilengkapi (5.6)
├── database/
│   └── migrations/
│       ├── 0001_01_01_000000_create_users_table.php               ✅
│       ├── 2026_05_16_000001_create_lowongans_table.php           ✅
│       ├── 2026_05_16_000002_add_role_to_users_table.php          ✅
│       ├── 2026_05_16_000003_create_lamarans_table.php            ✅
│       └── 2026_05_16_000004_create_kategori_pekerjaans_table.php 🔲 (5.6 - Ahza)
├── routes/
│   └── api.php                   ✅ (auth + lowongan + lamaran)
└── tests/
    ├── TestCase.php              ✅ (helper: authHeaders, makePenyedia, makePelamar)
    └── Feature/
        ├── AuthTest.php          ✅ (10 tests)
        ├── LowonganTest.php      ✅ (18 tests)
        └── LamaranTest.php       ✅ (12 tests)
```

---

## 4. Skema Database

### 4.1 Tabel `users` ✅
```
id              bigint PK
name            string
email           string UNIQUE
role            enum('penyedia','pelamar') DEFAULT 'pelamar'
nama_perusahaan string NULLABLE
telepon         string(20) NULLABLE
email_verified_at timestamp NULLABLE
password        string
remember_token  string NULLABLE
created_at / updated_at timestamps
```

### 4.2 Tabel `lowongans` ✅
```
id              bigint PK
user_id         FK → users.id (CASCADE DELETE)
kategori_id     unsignedBigInteger NULLABLE (belum FK constraint)
judul           string
deskripsi       text
kualifikasi     text
lokasi          string
jenis_pekerjaan enum('full-time','part-time','remote','kontrak')
gaji_min        bigint NULLABLE
gaji_max        bigint NULLABLE
batas_daftar    date
status          enum('aktif','nonaktif') DEFAULT 'aktif'
created_at / updated_at timestamps
```

### 4.3 Tabel `lamarans` 🔲 (dibuat oleh Ghani — 5.3)
```
id                  bigint PK
user_id             FK → users.id (CASCADE DELETE)
lowongan_id         FK → lowongans.id (CASCADE DELETE)
cv_path             string
surat_lamaran_path  string NULLABLE
status              enum('menunggu','diproses','wawancara','diterima','ditolak') DEFAULT 'menunggu'
catatan_penyedia    text NULLABLE
created_at / updated_at timestamps
UNIQUE(user_id, lowongan_id)  -- cegah double apply
```

### 4.4 Tabel `kategori_pekerjaans` 🔲 (dibuat oleh Ahza — 5.6)
```
id          bigint PK
nama        string UNIQUE
slug        string UNIQUE
deskripsi   text NULLABLE
created_at / updated_at timestamps
```

---

## 5. Spesifikasi Endpoint per Fitur

### 5.1 Auth — `AuthController` ✅
> Semua anggota terlibat. Sudah selesai.

| Method | Endpoint | Auth | Role |
|--------|----------|------|------|
| POST | `/api/auth/register` | ❌ | — |
| POST | `/api/auth/login` | ❌ | — |
| POST | `/api/auth/logout` | ✅ Bearer | semua |
| GET | `/api/auth/me` | ✅ Bearer | semua |
| POST | `/api/auth/refresh` | ✅ Bearer | semua |

**Field register:**
```json
{
  "name": "string|required",
  "email": "email|required|unique",
  "password": "string|min:8|required",
  "password_confirmation": "string|required",
  "role": "penyedia|pelamar (opsional, default pelamar)",
  "nama_perusahaan": "string|nullable (wajib jika role=penyedia)",
  "telepon": "string|nullable"
}
```

---

### 5.2 Manajemen Lowongan — `LowonganController` ✅
> Zulfikar. Sudah selesai. Hanya bisa diakses oleh `role=penyedia`.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/lowongan` | Semua lowongan milik penyedia yang login |
| POST | `/api/lowongan` | Buat lowongan baru |
| GET | `/api/lowongan/{id}` | Detail satu lowongan (owner only) |
| PUT | `/api/lowongan/{id}` | Update lowongan (owner only) |
| DELETE | `/api/lowongan/{id}` | Hapus lowongan (owner only) |
| PATCH | `/api/lowongan/{id}/status` | Toggle status aktif/nonaktif |

---

### 5.3 Fitur Pelamaran — `LamaranController` ✅
> **Dikerjakan oleh: Ghani Baskara Syah — SELESAI**

**Role yang bisa mengakses: `pelamar`**

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/lowongan` | Lihat semua lowongan `status=aktif` (berbeda dengan 5.2!) |
| GET | `/api/lowongan/{id}` | Lihat detail lowongan aktif |
| POST | `/api/lamaran` | Daftar pada lowongan (upload CV) |
| DELETE | `/api/lamaran/{id}` | Batalkan lamaran (hanya jika `status=menunggu`) |

**File yang harus dibuat:**
1. `database/migrations/2026_05_16_000003_create_lamarans_table.php`
2. `app/Models/Lamaran.php` — lengkapi dengan relasi & fillable
3. `app/Http/Controllers/Api/LamaranController.php`
4. Update `routes/api.php`

**Model `Lamaran.php` — relasi yang harus ada:**
```php
protected $fillable = [
    'user_id', 'lowongan_id', 'cv_path',
    'surat_lamaran_path', 'status', 'catatan_penyedia',
];

public function pelamar(): BelongsTo  // → User
public function lowongan(): BelongsTo // → Lowongan
```

**Logic penting `store()`:**
- Cek `Auth::user()->role === 'pelamar'`, jika bukan → 403
- Cek lowongan ada dan `status = aktif`, jika tidak → 422
- Cek belum melamar di lowongan yang sama (tangkap `QueryException` unique violation) → 422
- Upload `cv_path` ke `Storage::disk('public')`, path: `cv/{user_id}_{timestamp}.pdf`
- `surat_lamaran_path` opsional, pola upload sama
- `status` default `menunggu`, tidak boleh di-set oleh pelamar

**Logic penting `destroy()`:**
- Lamaran harus milik user yang login
- Status lamaran harus `menunggu`, jika sudah diproses → 422

**Response format (ikuti konvensi project):**
```json
{
  "message": "...",
  "data": { ... }
}
```

**Aturan routing di `api.php`:**
```php
// Tambahkan di dalam Route::middleware('auth:api')
Route::get('/lamaran', [LamaranController::class, 'indexPublic']);   // GET lowongan aktif (pelamar)
Route::post('/lamaran', [LamaranController::class, 'store']);
Route::delete('/lamaran/{id}', [LamaranController::class, 'destroy']);
```
> **Catatan:** GET `/api/lowongan` untuk pelamar melihat lowongan aktif bisa ditangani
> dengan middleware role check di `LowonganController@index` (bedakan berdasarkan role),
> atau buat route terpisah. Diskusikan dengan Zulfikar.

---

### 5.4 Manajemen Pelamar oleh Penyedia — `PelamarController` 🔲
> **Dikerjakan oleh: Septian Nuril Arifin**

**Role yang bisa mengakses: `penyedia`**

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/lowongan/{id}/pelamar` | Semua pelamar pada lowongan milik penyedia |
| GET | `/api/lamaran/{id}` | Detail satu lamaran (penyedia boleh akses lowongan miliknya) |
| PATCH | `/api/lamaran/{id}/status` | Update status lamaran pelamar |

**File yang harus dibuat:**
1. `app/Http/Controllers/Api/PelamarController.php`
2. Update `routes/api.php`

**Dependensi:** Tabel `lamarans` harus sudah ada (tunggu Ghani — 5.3).

**Logic penting:**
- `GET /api/lowongan/{id}/pelamar`: pastikan lowongan `user_id === Auth::id()` sebelum return pelamar
- `PATCH /api/lamaran/{id}/status`: validasi `status` hanya boleh nilai dari enum (`menunggu`,`diproses`,`wawancara`,`diterima`,`ditolak`); pastikan lowongan yang bersangkutan milik penyedia yang login
- Response harus include data pelamar (eager load `pelamar` dari relasi Lamaran → User)

**Aturan routing di `api.php`:**
```php
Route::get('/lowongan/{id}/pelamar', [PelamarController::class, 'index']);
Route::get('/lamaran/{id}', [PelamarController::class, 'show']);
Route::patch('/lamaran/{id}/status', [PelamarController::class, 'updateStatus']);
```

---

### 5.5 Pelacakan Status Lamaran — `StatusLamaranController` 🔲
> **Dikerjakan oleh: Husein Sidharta Muhammad**

**Role yang bisa mengakses: `pelamar`**

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/lamaran/saya` | Semua lamaran milik pelamar yang login |
| GET | `/api/lamaran/saya/{id}` | Detail & riwayat status satu lamaran |

**File yang harus dibuat:**
1. `app/Http/Controllers/Api/StatusLamaranController.php`
2. Update `routes/api.php`

**Dependensi:** Tabel `lamarans` harus sudah ada (tunggu Ghani — 5.3).

**Logic penting:**
- `GET /api/lamaran/saya`: filter `user_id = Auth::id()`, eager load relasi `lowongan`
- `GET /api/lamaran/saya/{id}`: pastikan lamaran `user_id === Auth::id()`, eager load `lowongan` dan `lowongan.penyedia`
- Response harus informatif: tampilkan status saat ini, info lowongan, dan info perusahaan penyedia

**Aturan routing di `api.php`:**
```php
// PENTING: Daftarkan route /lamaran/saya SEBELUM /lamaran/{id}
// agar Laravel tidak memperlakukan "saya" sebagai {id}
Route::get('/lamaran/saya', [StatusLamaranController::class, 'index']);
Route::get('/lamaran/saya/{id}', [StatusLamaranController::class, 'show']);
```

---

### 5.6 Kategori & Filter Lowongan — `KategoriController` 🔲
> **Dikerjakan oleh: Ahmad Ahza Ainurrahman**

**Akses terbuka untuk `semua` user terautentikasi (GET), `penyedia` untuk CUD.**

| Method | Endpoint | Deskripsi | Role |
|--------|----------|-----------|------|
| GET | `/api/kategori` | Lihat semua kategori | semua |
| POST | `/api/kategori` | Tambah kategori baru | penyedia |
| PUT | `/api/kategori/{id}` | Update kategori | penyedia |
| DELETE | `/api/kategori/{id}` | Hapus kategori | penyedia |
| GET | `/api/lowongan?kategori=IT` | Filter lowongan by nama/slug kategori | semua |

**File yang harus dibuat:**
1. `database/migrations/2026_05_16_000004_create_kategori_pekerjaans_table.php`
2. `app/Models/KategoriPekerjaan.php` — lengkapi dengan fillable & relasi
3. `app/Http/Controllers/Api/KategoriController.php`
4. Update `routes/api.php`
5. Update `LowonganController@index` untuk support query param `?kategori=`

**Model `KategoriPekerjaan.php`:**
```php
protected $fillable = ['nama', 'slug', 'deskripsi'];

public function lowongans(): HasMany // → Lowongan
```

**Logic filter di `LowonganController@index`:**
```php
// Tambahkan support query param ?kategori=
if ($request->has('kategori')) {
    $query->whereHas('kategori', fn($q) =>
        $q->where('nama', 'like', '%'.$request->kategori.'%')
          ->orWhere('slug', $request->kategori)
    );
}
```

**Aturan routing di `api.php`:**
```php
Route::get('/kategori', [KategoriController::class, 'index']);
Route::post('/kategori', [KategoriController::class, 'store']);
Route::put('/kategori/{id}', [KategoriController::class, 'update']);
Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);
```

> **Catatan penting:** Setelah migration `kategori_pekerjaans` selesai, tambahkan
> FK constraint `kategori_id` di tabel `lowongans` via migration baru:
> ```php
> $table->foreign('kategori_id')->references('id')->on('kategori_pekerjaans')->nullOnDelete();
> ```

---

## 6. Konvensi Kode

### 6.1 Format Response API
Selalu gunakan format berikut secara konsisten:

```json
// Sukses (data tunggal/koleksi)
{
  "message": "Pesan sukses yang deskriptif.",
  "data": { ... }
}

// Error
{
  "message": "Pesan error yang jelas."
}
```

> **Catatan:** Untuk endpoint DELETE yang sukses, tidak perlu menyertakan `data`.

HTTP status yang digunakan:
- `200` OK (GET, PUT, PATCH, DELETE berhasil)
- `201` Created (POST berhasil)
- `401` Unauthenticated
- `403` Forbidden (role salah / bukan owner)
- `404` Not Found
- `422` Unprocessable Entity (validasi gagal / business rule violation)

### 6.2 Role Check
Gunakan pengecekan manual di controller (belum ada middleware role khusus):
```php
if (Auth::user()->role !== 'penyedia') {
    return response()->json(['message' => 'Akses ditolak. Hanya untuk penyedia.'], 403);
}
```

### 6.3 Anotasi Swagger (akan dikerjakan belakangan)
Setiap method di controller **wajib** memiliki anotasi `@OA\` sesuai pola yang sudah ada di `LowonganController.php`. Saat ini anotasi baru ada di `LowonganController`. Package `darkaonline/l5-swagger` belum di-install — akan dikerjakan setelah semua fitur selesai.

Struktur minimal:
```php
/**
 * @OA\Get(
 *     path="/api/...",
 *     tags={"NamaTag"},
 *     summary="Deskripsi singkat",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="..."),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */
```

### 6.4 Naming Convention

| Komponen | Konvensi | Contoh |
|----------|----------|--------|
| Controller | PascalCase + `Controller` | `LamaranController` |
| Model | PascalCase | `KategoriPekerjaan` |
| Migration | `YYYY_MM_DD_NNNNNN_verb_table` | `2026_05_16_000003_create_lamarans_table` |
| Route | kebab-case (jika multi-kata) | `/api/lamaran/saya` |
| Kolom DB | snake_case | `cv_path`, `batas_daftar` |
| Response key | snake_case | `"data": {...}` |

### 6.5 File Upload (CV & Surat Lamaran)
- Disk: `Storage::disk('public')`
- Path: `cv/{user_id}_{timestamp}.{ext}` dan `surat/{user_id}_{timestamp}.{ext}`
- Validasi: `mimes:pdf,doc,docx`, `max:2048` (2MB)
- Simpan path relatif di DB (bukan full URL)
- Untuk akses publik: `Storage::url($cv_path)`
- Saat delete record: hapus file dari storage terlebih dahulu
- Di test: gunakan `Storage::fake('public')` + `UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf')`

### 6.6 Eager Loading
Selalu gunakan `with()` untuk menghindari N+1 problem:
```php
// Contoh
Lamaran::with('pelamar', 'lowongan.penyedia')->where('user_id', Auth::id())->get();
```

---

## 7. Setup & Jalankan Project

```bash
# 1. Clone & install
git clone <repo-url>
cd LamarIn
composer install --ignore-platform-reqs

# 2. Konfigurasi environment
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 3. Setup database (edit .env dulu ke MySQL)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=lamarin
# DB_USERNAME=root
# DB_PASSWORD=

php artisan migrate

# 4. Buat symlink storage (untuk file upload)
php artisan storage:link

# 5. Jalankan server
php artisan serve
```

> ⚠️ **PHP 8.3 wajib.** Jika error Symfony setelah pull, jalankan:
> `composer update --ignore-platform-reqs -W`

---

## 8. Header Request yang Wajib

```
Accept: application/json
Authorization: Bearer <token>   ← wajib untuk endpoint yang butuh auth
Content-Type: application/json  ← untuk request dengan body JSON
Content-Type: multipart/form-data ← untuk request dengan file upload
```

---

## 9. Urutan Pengerjaan yang Disarankan

Karena ada dependensi antar fitur, ikuti urutan ini:

```
5.1 Auth (✅ selesai & tested)
  ↓
5.2 Lowongan (✅ selesai & tested)
  ↓
5.3 Pelamaran (✅ selesai & tested)
  ↓
5.4 Manajemen Pelamar (Septian) ← butuh tabel lamarans dari 5.3 ✅
5.5 Pelacakan Status (Husein)   ← butuh tabel lamarans dari 5.3 ✅
5.6 Kategori (Ahza)             ← independen, bisa paralel
```

---

## 10. Checklist Sebelum Push ke Repo

- [ ] `php artisan migrate` berjalan tanpa error
- [ ] Endpoint baru sudah terdaftar di `routes/api.php`
- [ ] Model memiliki `$fillable` yang lengkap
- [ ] Model memiliki relasi yang sesuai
- [ ] Response mengikuti format standar `{message, data}`
- [ ] Role check sudah ada di setiap endpoint yang memerlukan
- [ ] Tidak ada `dd()`, `dump()`, atau `var_dump()` yang tertinggal
- [ ] Feature test dibuat dan semua PASSED (`php artisan test`)
- [ ] Header `Accept: application/json` sudah diuji di Postman/Insomnia

---

## 11. File Referensi

| File | Keterangan |
|------|------------|
| `app/Http/Controllers/Api/AuthController.php` | Referensi auth flow JWT |
| `app/Http/Controllers/Api/LowonganController.php` | Referensi konvensi response, validasi, Swagger annotation |
| `app/Models/Lowongan.php` | Referensi definisi model (fillable, casts, relasi) |
| `database/migrations/2026_05_16_000001_create_lowongans_table.php` | Referensi struktur migration |
| `README(Zulfikar).md` | Catatan teknis dari Zulfikar + TO DO untuk Ghani |
| `Proposal_LamarIn.pdf` | Proposal resmi project |
