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
                'message' => 'Akses ditolak. Hanya untuk pelamar.',
            ], 403);
        }

        // Ambil data lamaran beserta relasi informasi lowongannya
        $lamarans = Lamaran::with('lowongan')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Daftar riwayat lamaran berhasil diambil.',
            'data'    => $lamarans,
        ]);
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
                'message' => 'Akses ditolak. Hanya untuk pelamar.',
            ], 403);
        }

        // Cari lamaran berdasarkan ID dan pastikan itu milik user pelamar terkait
        $lamaran = Lamaran::with('lowongan.penyedia')->where('user_id', $user->id)->find($id);

        if (!$lamaran) {
            return response()->json([
                'message' => 'Data lamaran tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail lamaran berhasil diambil.',
            'data'    => $lamaran,
        ]);
    }
}