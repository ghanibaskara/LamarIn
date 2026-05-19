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