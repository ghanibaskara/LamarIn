<?php

namespace Tests\Feature;

use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelamarTest extends TestCase
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

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_penyedia_dapat_melihat_semua_pelamar_pada_lowongannya(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar1 = $this->makePelamar();
        $pelamar2 = $this->makePelamar(['email' => 'pelamar2@mail.com']);
        $lowongan = $this->createLowongan($penyedia);

        $this->createLamaran($pelamar1, $lowongan);
        $this->createLamaran($pelamar2, $lowongan);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->getJson("/api/lowongan/{$lowongan->id}/pelamar");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonPath('message', 'Daftar pelamar berhasil diambil.');
    }

    public function test_penyedia_mendapat_daftar_kosong_jika_belum_ada_pelamar(): void
    {
        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->getJson("/api/lowongan/{$lowongan->id}/pelamar");

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    public function test_penyedia_tidak_dapat_melihat_pelamar_lowongan_milik_penyedia_lain(): void
    {
        $penyedia1 = $this->makePenyedia();
        $penyedia2 = $this->makePenyedia(['email' => 'penyedia2@mail.com', 'nama_perusahaan' => 'PT Lain']);
        $lowongan  = $this->createLowongan($penyedia1);

        $response = $this->withHeaders($this->authHeaders($penyedia2))
                         ->getJson("/api/lowongan/{$lowongan->id}/pelamar");

        $response->assertStatus(403);
    }

    public function test_pelamar_tidak_dapat_mengakses_daftar_pelamar(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->getJson("/api/lowongan/{$lowongan->id}/pelamar");

        $response->assertStatus(403);
    }

    public function test_index_lowongan_tidak_ditemukan_mengembalikan_404(): void
    {
        $penyedia = $this->makePenyedia();

        $this->withHeaders($this->authHeaders($penyedia))
             ->getJson('/api/lowongan/9999/pelamar')
             ->assertStatus(404);
    }

    public function test_index_tanpa_token_mengembalikan_401(): void
    {
        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia);

        $this->getJson("/api/lowongan/{$lowongan->id}/pelamar")
             ->assertStatus(401);
    }

    // ── show ──────────────────────────────────────────────────────────────────

    public function test_penyedia_dapat_melihat_detail_lamaran_pada_lowongannya(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->getJson("/api/lamaran/{$lamaran->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $lamaran->id)
                 ->assertJsonPath('message', 'Detail lamaran berhasil diambil.');

        $this->assertArrayHasKey('pelamar', $response->json('data'));
    }

    public function test_penyedia_tidak_dapat_melihat_detail_lamaran_lowongan_milik_penyedia_lain(): void
    {
        $penyedia1 = $this->makePenyedia();
        $penyedia2 = $this->makePenyedia(['email' => 'penyedia2@mail.com', 'nama_perusahaan' => 'PT Lain']);
        $pelamar   = $this->makePelamar();
        $lowongan  = $this->createLowongan($penyedia1);
        $lamaran   = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($penyedia2))
                         ->getJson("/api/lamaran/{$lamaran->id}");

        $response->assertStatus(403);
    }

    public function test_pelamar_tidak_dapat_melihat_detail_lamaran_via_endpoint_penyedia(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->getJson("/api/lamaran/{$lamaran->id}");

        $response->assertStatus(403);
    }

    public function test_show_lamaran_tidak_ditemukan_mengembalikan_404(): void
    {
        $penyedia = $this->makePenyedia();

        $this->withHeaders($this->authHeaders($penyedia))
             ->getJson('/api/lamaran/9999')
             ->assertStatus(404);
    }

    // ── updateStatus ──────────────────────────────────────────────────────────

    public function test_penyedia_dapat_update_status_lamaran(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->patchJson("/api/lamaran/{$lamaran->id}/status", [
                             'status' => 'diproses',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', 'diproses')
                 ->assertJsonPath('message', 'Status lamaran berhasil diperbarui.');

        $this->assertDatabaseHas('lamarans', [
            'id'     => $lamaran->id,
            'status' => 'diproses',
        ]);
    }

    public function test_penyedia_dapat_update_status_beserta_catatan(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->patchJson("/api/lamaran/{$lamaran->id}/status", [
                             'status'           => 'wawancara',
                             'catatan_penyedia' => 'Harap hadir pada Senin pukul 09.00.',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', 'wawancara')
                 ->assertJsonPath('data.catatan_penyedia', 'Harap hadir pada Senin pukul 09.00.');
    }

    public function test_status_tidak_valid_ditolak(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->patchJson("/api/lamaran/{$lamaran->id}/status", [
                             'status' => 'invalid-status',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    public function test_penyedia_tidak_dapat_update_status_lamaran_lowongan_milik_penyedia_lain(): void
    {
        $penyedia1 = $this->makePenyedia();
        $penyedia2 = $this->makePenyedia(['email' => 'penyedia2@mail.com', 'nama_perusahaan' => 'PT Lain']);
        $pelamar   = $this->makePelamar();
        $lowongan  = $this->createLowongan($penyedia1);
        $lamaran   = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($penyedia2))
                         ->patchJson("/api/lamaran/{$lamaran->id}/status", [
                             'status' => 'diterima',
                         ]);

        $response->assertStatus(403);
    }

    public function test_pelamar_tidak_dapat_update_status_lamaran(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);
        $lamaran  = $this->createLamaran($pelamar, $lowongan);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->patchJson("/api/lamaran/{$lamaran->id}/status", [
                             'status' => 'diterima',
                         ]);

        $response->assertStatus(403);
    }

    public function test_update_status_lamaran_tidak_ditemukan_mengembalikan_404(): void
    {
        $penyedia = $this->makePenyedia();

        $this->withHeaders($this->authHeaders($penyedia))
             ->patchJson('/api/lamaran/9999/status', ['status' => 'diproses'])
             ->assertStatus(404);
    }
}
