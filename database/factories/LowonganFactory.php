<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LowonganFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'kategori_id'     => null,
            'judul'           => fake()->jobTitle(),
            'deskripsi'       => fake()->paragraph(),
            'kualifikasi'     => fake()->sentence(),
            'lokasi'          => fake()->city(),
            'jenis_pekerjaan' => fake()->randomElement(['full-time', 'part-time', 'remote', 'kontrak']),
            'gaji_min'        => fake()->numberBetween(3000000, 5000000),
            'gaji_max'        => fake()->numberBetween(5000000, 15000000),
            'batas_daftar'    => fake()->dateTimeBetween('+1 week', '+3 months'),
            'status'          => 'aktif',
        ];
    }
}