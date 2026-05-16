<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lowongans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->string('judul');
            $table->text('deskripsi');
            $table->text('kualifikasi');
            $table->string('lokasi');
            $table->enum('jenis_pekerjaan', ['full-time', 'part-time', 'remote', 'kontrak']);
            $table->bigInteger('gaji_min')->nullable();
            $table->bigInteger('gaji_max')->nullable();
            $table->date('batas_daftar');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lowongans');
    }
};