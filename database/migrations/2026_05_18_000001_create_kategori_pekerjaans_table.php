<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kategori_pekerjaans')) {
            Schema::create('kategori_pekerjaans', function (Blueprint $table) {
                $table->id();
                $table->string('nama_kategori', 100)->unique();
                $table->text('deskripsi')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_pekerjaans');
    }
};