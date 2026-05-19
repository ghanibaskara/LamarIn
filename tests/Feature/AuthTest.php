<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Register ─────────────────────────────────────────────────────────────

    public function test_register_sebagai_pelamar_default(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Budi Pelamar',
            'email'                 => 'budi@mail.com',
            'password'              => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.role', 'pelamar')
                 ->assertJsonStructure(['message', 'user', 'token' => ['access_token']]);
    }

    public function test_register_sebagai_penyedia_dengan_nama_perusahaan(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'PT Sukses',
            'email'                 => 'hr@ptsukses.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'penyedia',
            'nama_perusahaan'       => 'PT Sukses Makmur',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.role', 'penyedia')
                 ->assertJsonPath('user.nama_perusahaan', 'PT Sukses Makmur');
    }

    public function test_register_penyedia_tanpa_nama_perusahaan_gagal(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'HR Manager',
            'email'                 => 'hr@mail.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'penyedia',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nama_perusahaan']);
    }

    public function test_register_dengan_email_duplikat_gagal(): void
    {
        $this->makePelamar(['email' => 'sama@mail.com']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'User Lain',
            'email'                 => 'sama@mail.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_register_dengan_password_tidak_match_gagal(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'User',
            'email'                 => 'user@mail.com',
            'password'              => 'password123',
            'password_confirmation' => 'berbeda',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_login_berhasil(): void
    {
        $this->makePelamar(['email' => 'pelamar@mail.com', 'password' => 'password123']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'pelamar@mail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'user', 'token' => ['access_token', 'token_type', 'expires_in']]);
    }

    public function test_login_dengan_password_salah_gagal(): void
    {
        $this->makePelamar(['email' => 'pelamar@mail.com']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'pelamar@mail.com',
            'password' => 'salah_password',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_dengan_email_tidak_ada_gagal(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'tidakada@mail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    // ── Me ────────────────────────────────────────────────────────────────────

    public function test_me_mengembalikan_data_user_login(): void
    {
        $user = $this->makePelamar();

        $response = $this->withHeaders($this->authHeaders($user))
                         ->getJson('/api/auth/me');

        $response->assertStatus(200)
                 ->assertJsonPath('user.id', $user->id)
                 ->assertJsonPath('user.email', $user->email);
    }

    public function test_me_tanpa_token_gagal(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_logout_berhasil(): void
    {
        $user = $this->makePelamar();

        $response = $this->withHeaders($this->authHeaders($user))
                         ->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Logout successful.');
    }
}
