<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lamarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lowongan_id')->constrained('lowongans')->onDelete('cascade');
            $table->string('cv_path');
            $table->string('surat_lamaran_path')->nullable();
            $table->enum('status', ['menunggu', 'diproses', 'wawancara', 'diterima', 'ditolak'])->default('menunggu');
            $table->text('catatan_penyedia')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lowongan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lamarans');
    }
};
