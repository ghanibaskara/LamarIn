<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Status Lamaran",
 *     description="Pelacakan Status Lamaran oleh Pelamar"
 * )
 */
class StatusLamaranController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/lamaran/saya",
     *     tags={"Status Lamaran"},
     *     summary="Melihat semua riwayat lamaran milik pelamar yang login",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Daftar riwayat lamaran berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden — bukan pelamar")
     * )
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
     * @OA\Get(
     *     path="/api/lamaran/saya/{id}",
     *     tags={"Status Lamaran"},
     *     summary="Melihat detail satu lamaran milik pelamar berdasarkan ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detail lamaran berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden — bukan pelamar"),
     *     @OA\Response(response=404, description="Lamaran tidak ditemukan")
     * )
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