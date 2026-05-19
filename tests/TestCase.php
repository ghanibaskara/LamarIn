<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getTokenForUser(User $user): string
    {
        return auth('api')->login($user);
    }

    protected function authHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getTokenForUser($user),
            'Accept'        => 'application/json',
        ];
    }

    protected function makePenyedia(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'            => 'penyedia',
            'nama_perusahaan' => 'PT Contoh Jaya',
        ], $overrides));
    }

    protected function makePelamar(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'pelamar',
        ], $overrides));
    }
}
