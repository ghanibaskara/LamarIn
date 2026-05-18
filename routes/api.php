<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LamaranController;
use App\Http\Controllers\Api\LowonganController;
use App\Http\Controllers\Api\PelamarController;
use App\Http\Controllers\Api\StatusLamaranController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('/lowongan', [LowonganController::class, 'index']);
    Route::post('/lowongan', [LowonganController::class, 'store']);
    Route::get('/lowongan/{id}', [LowonganController::class, 'show']);
    Route::put('/lowongan/{id}', [LowonganController::class, 'update']);
    Route::delete('/lowongan/{id}', [LowonganController::class, 'destroy']);
    Route::patch('/lowongan/{id}/status', [LowonganController::class, 'updateStatus']);

    // 5.5 — Pelacakan Status Lamaran (Husein)
    Route::get('/lamaran/saya', [StatusLamaranController::class, 'index']);
    Route::get('/lamaran/saya/{id}', [StatusLamaranController::class, 'show']);

    Route::post('/lamaran', [LamaranController::class, 'store']);
    Route::delete('/lamaran/{id}', [LamaranController::class, 'destroy']);

    // 5.4 — Manajemen Pelamar oleh Penyedia (Septian)
    // CATATAN: Saat 5.5 ditambahkan, route /lamaran/saya HARUS didaftarkan SEBELUM /lamaran/{id}
    Route::get('/lowongan/{id}/pelamar', [PelamarController::class, 'index']);
    Route::get('/lamaran/{id}', [PelamarController::class, 'show']);
    Route::patch('/lamaran/{id}/status', [PelamarController::class, 'updateStatus']);
});