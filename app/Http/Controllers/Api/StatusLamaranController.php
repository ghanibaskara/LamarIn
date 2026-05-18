<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatusLamaranController extends Controller
{
    /**
     * Mengambil daftar seluruh lamaran milik pelamar yang sedang login.
     */
    public function index()
    {
        $user = Auth::user();

        // Validasi Role: Pastikan hanya Pelamar yang bisa mengakses
        if ($user->role !== 'pelamar') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Hanya pelamar yang dapat mengakses data ini.'
            ], 403);
        }

        // Ambil data lamaran beserta relasi informasi lowongannya
        $lamarans = Lamaran::with('lowongan')->where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat lamaran berhasil diambil.',
            'data' => $lamarans
        ], 200);
    }

    /**
     * Menampilkan detail satu lamaran berdasarkan ID milik pelamar.
     */
    public function show($id)
    {
        $user = Auth::user();

        // Validasi Role
        if ($user->role !== 'pelamar') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Hanya pelamar yang dapat mengakses data ini.'
            ], 403);
        }

        // Cari lamaran berdasarkan ID dan pastikan itu milik user pelamar terkait
        $lamaran = Lamaran::with('lowongan')->where('user_id', $user->id)->find($id);

        if (!$lamaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data lamaran tidak ditemukan atau Anda tidak memiliki akses.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail lamaran berhasil diambil.',
            'data' => $lamaran
        ], 200);
    }
}