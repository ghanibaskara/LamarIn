<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lamaran extends Model
{
    protected $fillable = [
        'user_id',
        'lowongan_id',
        'cv_path',
        'surat_lamaran_path',
        'status',
        'catatan_penyedia',
    ];

    public function pelamar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lowongan(): BelongsTo
    {
        return $this->belongsTo(Lowongan::class, 'lowongan_id');
    }
}
