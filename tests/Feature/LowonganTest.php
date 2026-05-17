<?php

namespace Tests\Feature;

use App\Models\Lowongan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowonganTest extends TestCase
{
    use RefreshDatabase;

    private function lowonganData(array $overrides = []): array
    {
        return array_merge([
            'judul'           => 'Backend Developer',
            'deskripsi'       => 'Membangun REST API dengan Laravel',
            'kualifikasi'     => 'Min. S1 Informatika, pengalaman 1 tahun',
            'lokasi'          => 'Malang',
            'jenis_pekerjaan' => 'full-time',
            'gaji_min'        => 5000000,
            'gaji_max'        => 10000000,
            'batas_daftar'    => now()->addMonth()->format('Y-m-d'),
        ], $overrides);
    }

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

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_penyedia_hanya_melihat_lowongan_miliknya(): void
    {
        $penyedia1 = $this->makePenyedia();
        $penyedia2 = $this->makePenyedia(['email' => 'penyedia2@mail.com']);
        $this->createLowongan($penyedia1);
        $this->createLowongan($penyedia2);

        $response = $this->withHeaders($this->authHeaders($penyedia1))
                         ->getJson('/api/lowongan');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($penyedia1->id, $data[0]['user_id']);
    }

    public function test_pelamar_melihat_semua_lowongan_aktif(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $this->createLowongan($penyedia, ['status' => 'aktif']);
        $this->createLowongan($penyedia, ['judul' => 'Frontend Dev', 'status' => 'nonaktif']);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->getJson('/api/lowongan');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('aktif', $data[0]['status']);
    }

    public function test_index_tanpa_token_gagal(): void
    {
        $this->getJson('/api/lowongan')->assertStatus(401);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_penyedia_dapat_membuat_lowongan(): void
    {
        $penyedia = $this->makePenyedia();

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->postJson('/api/lowongan', $this->lowonganData());

        $response->assertStatus(201)
                 ->assertJsonPath('data.judul', 'Backend Developer')
                 ->assertJsonPath('data.user_id', $penyedia->id);
    }

    public function test_pelamar_tidak_dapat_membuat_lowongan(): void
    {
        $pelamar = $this->makePelamar();

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lowongan', $this->lowonganData());

        $response->assertStatus(403);
    }

    public function test_store_validasi_field_wajib_gagal(): void
    {
        $penyedia = $this->makePenyedia();

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->postJson('/api/lowongan', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['judul', 'deskripsi', 'kualifikasi', 'lokasi', 'jenis_pekerjaan', 'batas_daftar']);
    }

    public function test_store_batas_daftar_masa_lalu_gagal(): void
    {
        $penyedia = $this->makePenyedia();

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->postJson('/api/lowongan', $this->lowonganData([
                             'batas_daftar' => now()->subDay()->format('Y-m-d'),
                         ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['batas_daftar']);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_penyedia_dapat_melihat_lowongan_miliknya(): void
    {
        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->getJson("/api/lowongan/{$lowongan->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $lowongan->id);
    }

    public function test_penyedia_tidak_dapat_melihat_lowongan_milik_penyedia_lain(): void
    {
        $penyedia1 = $this->makePenyedia();
        $penyedia2 = $this->makePenyedia(['email' => 'penyedia2@mail.com']);
        $lowongan  = $this->createLowongan($penyedia2);

        $response = $this->withHeaders($this->authHeaders($penyedia1))
                         ->getJson("/api/lowongan/{$lowongan->id}");

        $response->assertStatus(403);
    }

    public function test_pelamar_dapat_melihat_detail_lowongan_aktif(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia, ['status' => 'aktif']);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->getJson("/api/lowongan/{$lowongan->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $lowongan->id);
    }

    public function test_pelamar_tidak_dapat_melihat_lowongan_nonaktif(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia, ['status' => 'nonaktif']);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->getJson("/api/lowongan/{$lowongan->id}");

        $response->assertStatus(404);
    }

    public function test_show_lowongan_tidak_ada_mengembalikan_404(): void
    {
        $penyedia = $this->makePenyedia();

        $this->withHeaders($this->authHeaders($penyedia))
             ->getJson('/api/lowongan/9999')
             ->assertStatus(404);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_penyedia_dapat_update_lowongan_miliknya(): void
    {
        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->putJson("/api/lowongan/{$lowongan->id}", [
                             'judul' => 'Senior Backend Developer',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.judul', 'Senior Backend Developer');
    }

    public function test_pelamar_tidak_dapat_update_lowongan(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $this->withHeaders($this->authHeaders($pelamar))
             ->putJson("/api/lowongan/{$lowongan->id}", ['judul' => 'Hack'])
             ->assertStatus(403);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_penyedia_dapat_hapus_lowongan_miliknya(): void
    {
        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia);

        $this->withHeaders($this->authHeaders($penyedia))
             ->deleteJson("/api/lowongan/{$lowongan->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('lowongans', ['id' => $lowongan->id]);
    }

    public function test_pelamar_tidak_dapat_hapus_lowongan(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $this->withHeaders($this->authHeaders($pelamar))
             ->deleteJson("/api/lowongan/{$lowongan->id}")
             ->assertStatus(403);
    }

    // ── Update Status ─────────────────────────────────────────────────────────

    public function test_penyedia_dapat_ubah_status_lowongan(): void
    {
        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia, ['status' => 'aktif']);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->patchJson("/api/lowongan/{$lowongan->id}/status", [
                             'status' => 'nonaktif',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', 'nonaktif');
    }

    public function test_pelamar_tidak_dapat_ubah_status_lowongan(): void
    {
        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $this->withHeaders($this->authHeaders($pelamar))
             ->patchJson("/api/lowongan/{$lowongan->id}/status", ['status' => 'nonaktif'])
             ->assertStatus(403);
    }

    public function test_update_status_nilai_tidak_valid_gagal(): void
    {
        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia);

        $this->withHeaders($this->authHeaders($penyedia))
             ->patchJson("/api/lowongan/{$lowongan->id}/status", ['status' => 'dihapus'])
             ->assertStatus(422);
    }
}
