<?php

namespace Tests\Feature;

use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LamaranTest extends TestCase
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

    private function fakeCV(): UploadedFile
    {
        return UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf');
    }

    private function fakeSurat(): UploadedFile
    {
        return UploadedFile::fake()->create('surat.pdf', 300, 'application/pdf');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_pelamar_dapat_melamar_lowongan_aktif(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lamaran', [
                             'lowongan_id' => $lowongan->id,
                             'cv'          => $this->fakeCV(),
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.lowongan_id', $lowongan->id)
                 ->assertJsonPath('data.status', 'menunggu');

        $this->assertDatabaseHas('lamarans', [
            'user_id'    => $pelamar->id,
            'lowongan_id'=> $lowongan->id,
            'status'     => 'menunggu',
        ]);
    }

    public function test_pelamar_dapat_melamar_dengan_surat_lamaran(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lamaran', [
                             'lowongan_id'   => $lowongan->id,
                             'cv'            => $this->fakeCV(),
                             'surat_lamaran' => $this->fakeSurat(),
                         ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.surat_lamaran_path'));
    }

    public function test_penyedia_tidak_dapat_melamar(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia);

        $response = $this->withHeaders($this->authHeaders($penyedia))
                         ->postJson('/api/lamaran', [
                             'lowongan_id' => $lowongan->id,
                             'cv'          => $this->fakeCV(),
                         ]);

        $response->assertStatus(403);
    }

    public function test_tidak_dapat_melamar_dua_kali_pada_lowongan_yang_sama(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $this->withHeaders($this->authHeaders($pelamar))
             ->postJson('/api/lamaran', [
                 'lowongan_id' => $lowongan->id,
                 'cv'          => $this->fakeCV(),
             ]);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lamaran', [
                             'lowongan_id' => $lowongan->id,
                             'cv'          => $this->fakeCV(),
                         ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Anda sudah melamar pada lowongan ini.');
    }

    public function test_tidak_dapat_melamar_lowongan_nonaktif(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia, ['status' => 'nonaktif']);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lamaran', [
                             'lowongan_id' => $lowongan->id,
                             'cv'          => $this->fakeCV(),
                         ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Lowongan ini tidak sedang aktif.');
    }

    public function test_tidak_dapat_melamar_lowongan_expired(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia, ['batas_daftar' => now()->subDay()->format('Y-m-d')]);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lamaran', [
                             'lowongan_id' => $lowongan->id,
                             'cv'          => $this->fakeCV(),
                         ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Batas pendaftaran lowongan sudah lewat.');
    }

    public function test_dapat_melamar_lowongan_pada_hari_batas_daftar(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        // Set test now to noon, and batas_daftar today
        $now = now();
        $lowongan = $this->createLowongan($penyedia, ['batas_daftar' => $now->format('Y-m-d')]);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lamaran', [
                             'lowongan_id' => $lowongan->id,
                             'cv'          => $this->fakeCV(),
                         ]);

        $response->assertStatus(201);
    }

    public function test_tidak_dapat_melamar_lowongan_yang_tidak_ada(): void
    {
        Storage::fake('public');

        $pelamar = $this->makePelamar();

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lamaran', [
                             'lowongan_id' => 9999,
                             'cv'          => $this->fakeCV(),
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['lowongan_id']);
    }

    public function test_lamaran_tanpa_cv_gagal(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->postJson('/api/lamaran', [
                             'lowongan_id' => $lowongan->id,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['cv']);
    }

    public function test_lamaran_tanpa_token_gagal(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $lowongan = $this->createLowongan($penyedia);

        $this->postJson('/api/lamaran', [
            'lowongan_id' => $lowongan->id,
            'cv'          => $this->fakeCV(),
        ])->assertStatus(401);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_pelamar_dapat_batalkan_lamaran_berstatus_menunggu(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $postResponse = $this->withHeaders($this->authHeaders($pelamar))
                             ->postJson('/api/lamaran', [
                                 'lowongan_id' => $lowongan->id,
                                 'cv'          => $this->fakeCV(),
                             ]);

        $lamaranId = $postResponse->json('data.id');

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->deleteJson("/api/lamaran/{$lamaranId}");

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Lamaran berhasil dibatalkan.');

        $this->assertDatabaseMissing('lamarans', ['id' => $lamaranId]);
    }

    public function test_tidak_dapat_batalkan_lamaran_yang_sudah_diproses(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $lamaran = Lamaran::create([
            'user_id'    => $pelamar->id,
            'lowongan_id'=> $lowongan->id,
            'cv_path'    => 'cv/dummy.pdf',
            'status'     => 'diproses',
        ]);

        $response = $this->withHeaders($this->authHeaders($pelamar))
                         ->deleteJson("/api/lamaran/{$lamaran->id}");

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Lamaran tidak dapat dibatalkan karena sudah diproses.');
    }

    public function test_tidak_dapat_batalkan_lamaran_milik_pelamar_lain(): void
    {
        Storage::fake('public');

        $penyedia  = $this->makePenyedia();
        $pelamar1  = $this->makePelamar();
        $pelamar2  = $this->makePelamar(['email' => 'pelamar2@mail.com']);
        $lowongan  = $this->createLowongan($penyedia);

        $lamaran = Lamaran::create([
            'user_id'    => $pelamar1->id,
            'lowongan_id'=> $lowongan->id,
            'cv_path'    => 'cv/dummy.pdf',
            'status'     => 'menunggu',
        ]);

        $response = $this->withHeaders($this->authHeaders($pelamar2))
                         ->deleteJson("/api/lamaran/{$lamaran->id}");

        $response->assertStatus(403);
    }

    public function test_batalkan_lamaran_yang_tidak_ada_mengembalikan_404(): void
    {
        $pelamar = $this->makePelamar();

        $this->withHeaders($this->authHeaders($pelamar))
             ->deleteJson('/api/lamaran/9999')
             ->assertStatus(404);
    }

    public function test_penyedia_tidak_dapat_batalkan_lamaran(): void
    {
        Storage::fake('public');

        $penyedia = $this->makePenyedia();
        $pelamar  = $this->makePelamar();
        $lowongan = $this->createLowongan($penyedia);

        $lamaran = Lamaran::create([
            'user_id'    => $pelamar->id,
            'lowongan_id'=> $lowongan->id,
            'cv_path'    => 'cv/dummy.pdf',
            'status'     => 'menunggu',
        ]);

        $this->withHeaders($this->authHeaders($penyedia))
             ->deleteJson("/api/lamaran/{$lamaran->id}")
             ->assertStatus(403);
    }
}
