<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LamaranController;
use App\Http\Controllers\Api\LowonganController;
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

    Route::post('/lamaran', [LamaranController::class, 'store']);
    Route::delete('/lamaran/{id}', [LamaranController::class, 'destroy']);
});