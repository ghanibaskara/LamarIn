<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPekerjaan extends Model
{
    use HasFactory;

    protected $table = 'kategori_pekerjaans';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
    ];

    public function lowongans(): HasMany
    {
        return $this->hasMany(Lowongan::class, 'kategori_id');
    }
}