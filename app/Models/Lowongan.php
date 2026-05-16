<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lowongan extends Model
{
    protected $fillable = [
        'user_id',
        'kategori_id',
        'judul',
        'deskripsi',
        'kualifikasi',
        'lokasi',
        'jenis_pekerjaan',
        'gaji_min',
        'gaji_max',
        'batas_daftar',
        'status',
    ];

    protected $casts = [
        'batas_daftar' => 'date',
        'gaji_min'     => 'integer',
        'gaji_max'     => 'integer',
    ];

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(\App\Models\KategoriPekerjaan::class, 'kategori_id');
    }

    public function lamarans(): HasMany
    {
        return $this->hasMany(\App\Models\Lamaran::class, 'lowongan_id');
    }
}