<?php

namespace Tests\Feature;

use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusLamaranTest extends TestCase
{
    use RefreshDatabase;

    private function createLowongan($penyedia, array $overrides = []): Lowongan
    {
        return Lowongan::create(array_merge([
            'user_id'         => $penyedia->id,
            'judul'           => 'Backend Developer',
            'deskripsi'       => 'Deskripsi lowongan',
            'kualifikasi'     => 'S1 Informatika',
            'lokasi'          => 'Malang',
            'jenis_pekerjaan' => 'full-time',
            'batas_daftar'    => now()->addMonth()->format('Y-m-d'),
            'status'          => 'aktif',
        ], $overrides));
    }

    private function createLamaran($pelamar, $lowongan, array $overrides = []): Lamaran
    {
        return Lamaran::create(array_merge([
            'user_id'     => $pelamar->id,
            'lowongan_id' => $lowongan->id,
            'cv_path'     => 'cv/dummy.pdf',
            'status'      => 'menunggu',
        ], $overrides));
    }

    // ── index: GET /api/lamaran/saya ──────────────────────────────────────────

    public function test_pelamar_dapat_melihat_semua_lamarannya(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan1 = $this->createLowongan($penyedia);
        $lowongan2 = $this->createLowongan($penyedia, ['judul' => 'Frontend Dev']);

        $this->createLamaran($pelamar, $lowongan1);
        $this->createLamaran($pelamar, $lowongan2);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->getJson('/api/lamaran/saya');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonPath('message', 'Daftar riwayat lamaran berhasil diambil.');
    }

    public function test_pelamar_hanya_melihat_lamaran_miliknya_sendiri(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar1 = $this->makePelamar();
        $pelamar2 = $this->makePelamar(['email' => 'pelamar2@mail.com']);
        $lowongan = $this->createLowongan($penyedia);

        $this->createLamaran($pelamar1, $lowongan);
        $this->createLamaran($pelamar2, $lowongan);

        $response = $this->withHeaders($this->authHeaders($pelamar1))
                         ->getJson('/api/lamaran/saya');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_pelamar_mendapat_daftar_kosong_jika_belum_melamar(): void
    {
        $pelamar = $this->makePelamar();

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->getJson('/api/lamaran/saya');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    public function test_penyedia_tidak_dapat_mengakses_lamaran_saya(): void
    {
        $penyedia = $this->makePenyedia();

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->getJson('/api/lamaran/saya');

        $response->assertStatus(403);
    }

    public function test_index_tanpa_token_mengembalikan_401(): void
    {
        $this->getJson('/api/lamaran/saya')->assertStatus(401);
    }

    // ── show: GET /api/lamaran/saya/{id} ─────────────────────────────────────

    public function test_pelamar_dapat_melihat_detail_lamarannya(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->getJson("/api/lamaran/saya/{$lamaran->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $lamaran->id)
                 ->assertJsonPath('message', 'Detail lamaran berhasil diambil.');

        // Verifikasi eager load lowongan.penyedia
        $this->assertArrayHasKey('lowongan', $response->json('data'));
        $this->assertArrayHasKey('penyedia', $response->json('data.lowongan'));
    }

    public function test_pelamar_tidak_dapat_melihat_detail_lamaran_milik_pelamar_lain(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar1 = $this->makePelamar();
        $pelamar2 = $this->makePelamar(['email' => 'pelamar2@mail.com']);
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar1, $lowongan);

        $response = $this->withHeaders($this->authHeaders($pelamar2))
                         ->getJson("/api/lamaran/saya/{$lamaran->id}");

        $response->assertStatus(404);
    }

    public function test_show_lamaran_tidak_ditemukan_mengembalikan_404(): void
    {
        $pelamar = $this->makePelamar();

        $this->withHeaders($this->authHeaders($pelamar))
             ->getJson('/api/lamaran/saya/9999')
             ->assertStatus(404);
    }

    public function test_penyedia_tidak_dapat_mengakses_detail_lamaran_saya(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->getJson("/api/lamaran/saya/{$lamaran->id}");

        $response->assertStatus(403);
    }

    public function test_show_tanpa_token_mengembalikan_401(): void
    {
        $this->getJson('/api/lamaran/saya/1')->assertStatus(401);
    }
}
