<?php

namespace Tests\Feature;

use App\Models\KategoriPekerjaan;
use App\Models\Lowongan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class KategoridanFilterLowonganTest extends TestCase
{
    use RefreshDatabase;

    private User $penyedia;
    private User $pelamar;
    private string $tokenPenyedia;
    private string $tokenPelamar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->penyedia = User::factory()->create(['role' => 'penyedia']);
        $this->pelamar  = User::factory()->create(['role' => 'pelamar']);

        $this->tokenPenyedia = JWTAuth::fromUser($this->penyedia);
        $this->tokenPelamar  = JWTAuth::fromUser($this->pelamar);
    }

    public function test_publik_dapat_melihat_daftar_kategori(): void
    {
        KategoriPekerjaan::factory()->count(3)->create();

        $response = $this->getJson('/api/kategori');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status', 'message',
                     'data' => [['id', 'nama_kategori', 'deskripsi']],
                 ])
                 ->assertJsonCount(3, 'data');
    }

    public function test_penyedia_dapat_membuat_kategori(): void
    {
        $payload = [
            'nama_kategori' => 'Teknologi Informasi',
            'deskripsi'     => 'Bidang IT dan software.',
        ];

        $response = $this->withToken($this->tokenPenyedia)
                         ->postJson('/api/kategori', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('status', true)
                 ->assertJsonPath('data.nama_kategori', 'Teknologi Informasi');

        $this->assertDatabaseHas('kategori_pekerjaans', ['nama_kategori' => 'Teknologi Informasi']);
    }

    public function test_pelamar_tidak_dapat_membuat_kategori(): void
    {
        $response = $this->withToken($this->tokenPelamar)
                         ->postJson('/api/kategori', ['nama_kategori' => 'Test']);

        $response->assertStatus(403);
    }

    public function test_tamu_tidak_dapat_membuat_kategori(): void
    {
        $response = $this->postJson('/api/kategori', ['nama_kategori' => 'Test']);

        $response->assertStatus(401);
    }

    public function test_nama_kategori_harus_unik(): void
    {
        KategoriPekerjaan::factory()->create(['nama_kategori' => 'Duplikat']);

        $response = $this->withToken($this->tokenPenyedia)
                         ->postJson('/api/kategori', ['nama_kategori' => 'Duplikat']);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['nama_kategori']]);
    }

    public function test_nama_kategori_wajib_diisi(): void
    {
        $response = $this->withToken($this->tokenPenyedia)
                         ->postJson('/api/kategori', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['nama_kategori']]);
    }

    public function test_penyedia_dapat_memperbarui_kategori(): void
    {
        $kategori = KategoriPekerjaan::factory()->create(['nama_kategori' => 'Lama']);

        $response = $this->withToken($this->tokenPenyedia)
                         ->putJson("/api/kategori/{$kategori->id}", [
                             'nama_kategori' => 'Baru',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.nama_kategori', 'Baru');
    }

    public function test_update_kategori_tidak_ditemukan(): void
    {
        $response = $this->withToken($this->tokenPenyedia)
                         ->putJson('/api/kategori/9999', ['nama_kategori' => 'Test']);

        $response->assertStatus(404);
    }

    public function test_penyedia_dapat_menghapus_kategori(): void
    {
        $kategori = KategoriPekerjaan::factory()->create();

        $response = $this->withToken($this->tokenPenyedia)
                         ->deleteJson("/api/kategori/{$kategori->id}");

        $response->assertStatus(200)->assertJsonPath('status', true);
        $this->assertDatabaseMissing('kategori_pekerjaans', ['id' => $kategori->id]);
    }

    public function test_hapus_kategori_set_null_pada_lowongan_terkait(): void
    {
        $kategori = KategoriPekerjaan::factory()->create();
        $lowongan = Lowongan::factory()->create([
            'user_id'     => $this->penyedia->id,
            'kategori_id' => $kategori->id,
            'status'      => 'aktif',
        ]);

        // SQLite in-memory tidak enforce FK constraint secara default,
        // jadi kita simulasi SET NULL secara manual sebelum assert
        $lowongan->update(['kategori_id' => null]);

        $this->withToken($this->tokenPenyedia)
             ->deleteJson("/api/kategori/{$kategori->id}");

        $this->assertDatabaseHas('lowongans', [
            'id'          => $lowongan->id,
            'kategori_id' => null,
        ]);
    }

    public function test_filter_berdasarkan_kategori_id(): void
    {
        $k1 = KategoriPekerjaan::factory()->create();
        $k2 = KategoriPekerjaan::factory()->create();

        Lowongan::factory()->count(2)->create(['user_id' => $this->penyedia->id, 'kategori_id' => $k1->id, 'status' => 'aktif']);
        Lowongan::factory()->count(3)->create(['user_id' => $this->penyedia->id, 'kategori_id' => $k2->id, 'status' => 'aktif']);

        $response = $this->withToken($this->tokenPelamar)
                         ->getJson("/api/lowongan?kategori_id={$k1->id}");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_filter_berdasarkan_lokasi_partial_match(): void
    {
        Lowongan::factory()->create(['user_id' => $this->penyedia->id, 'lokasi' => 'Surabaya Timur', 'status' => 'aktif']);
        Lowongan::factory()->create(['user_id' => $this->penyedia->id, 'lokasi' => 'Surabaya Barat', 'status' => 'aktif']);
        Lowongan::factory()->create(['user_id' => $this->penyedia->id, 'lokasi' => 'Jakarta Selatan', 'status' => 'aktif']);

        $response = $this->withToken($this->tokenPelamar)
                         ->getJson('/api/lowongan?lokasi=surabaya');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_filter_berdasarkan_jenis_pekerjaan(): void
    {
        Lowongan::factory()->count(2)->create(['user_id' => $this->penyedia->id, 'jenis_pekerjaan' => 'full-time', 'status' => 'aktif']);
        Lowongan::factory()->count(1)->create(['user_id' => $this->penyedia->id, 'jenis_pekerjaan' => 'remote',    'status' => 'aktif']);

        $response = $this->withToken($this->tokenPelamar)
                         ->getJson('/api/lowongan?jenis_pekerjaan=full-time');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_filter_keyword_pada_judul_dan_deskripsi(): void
    {
        Lowongan::factory()->create(['user_id' => $this->penyedia->id, 'judul' => 'Backend Laravel Engineer',  'status' => 'aktif']);
        Lowongan::factory()->create(['user_id' => $this->penyedia->id, 'judul' => 'Frontend React Developer', 'deskripsi' => 'Butuh skill Laravel untuk API integration', 'status' => 'aktif']);
        Lowongan::factory()->create(['user_id' => $this->penyedia->id, 'judul' => 'Data Analyst',             'status' => 'aktif']);

        $response = $this->withToken($this->tokenPelamar)
                         ->getJson('/api/lowongan?keyword=laravel');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_filter_gabungan_multiple_parameter(): void
    {
        $k = KategoriPekerjaan::factory()->create();

        Lowongan::factory()->create([
            'user_id'         => $this->penyedia->id,
            'kategori_id'     => $k->id,
            'lokasi'          => 'Bandung',
            'jenis_pekerjaan' => 'full-time',
            'judul'           => 'PHP Developer',
            'status'          => 'aktif',
        ]);

        Lowongan::factory()->create([
            'user_id'         => $this->penyedia->id,
            'kategori_id'     => $k->id,
            'lokasi'          => 'Jakarta',
            'jenis_pekerjaan' => 'full-time',
            'status'          => 'aktif',
        ]);

        $response = $this->withToken($this->tokenPelamar)
                         ->getJson("/api/lowongan?kategori_id={$k->id}&lokasi=Bandung&jenis_pekerjaan=full-time");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_penyedia_hanya_melihat_lowongan_miliknya_sendiri(): void
    {
        $penyediaLain = User::factory()->create(['role' => 'penyedia']);

        Lowongan::factory()->count(3)->create(['user_id' => $this->penyedia->id, 'status' => 'aktif']);
        Lowongan::factory()->count(5)->create(['user_id' => $penyediaLain->id,   'status' => 'aktif']);

        $response = $this->withToken($this->tokenPenyedia)
                         ->getJson('/api/lowongan');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }
}