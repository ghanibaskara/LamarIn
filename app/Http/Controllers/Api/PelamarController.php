<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Pelamar",
 *     description="Manajemen Pelamar oleh Penyedia"
 * )
 */
class PelamarController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/lowongan/{id}/pelamar",
     *     tags={"Pelamar"},
     *     summary="Melihat semua pelamar pada lowongan milik penyedia",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="ID Lowongan"),
     *     @OA\Response(response=200, description="Daftar pelamar berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden — bukan penyedia atau bukan lowongan milik sendiri"),
     *     @OA\Response(response=404, description="Lowongan tidak ditemukan")
     * )
     */
    public function index(string $id): JsonResponse
    {
        if (Auth::user()->role !== 'penyedia') {
            return response()->json(['message' => 'Akses ditolak. Hanya untuk penyedia.'], 403);
        }

        $lowongan = Lowongan::find($id);

        if (! $lowongan) {
            return response()->json(['message' => 'Lowongan tidak ditemukan.'], 404);
        }

        if ($lowongan->user_id !== Auth::id()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $lamarans = Lamaran::with('pelamar')
            ->where('lowongan_id', $id)
            ->get();

        return response()->json([
            'message' => 'Daftar pelamar berhasil diambil.',
            'data'    => $lamarans,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/lamaran/{id}",
     *     tags={"Pelamar"},
     *     summary="Melihat detail satu lamaran (hanya Penyedia pemilik lowongan)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="ID Lamaran"),
     *     @OA\Response(response=200, description="Detail lamaran berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lamaran tidak ditemukan")
     * )
     */
    public function show(string $id): JsonResponse
    {
        if (Auth::user()->role !== 'penyedia') {
            return response()->json(['message' => 'Akses ditolak. Hanya untuk penyedia.'], 403);
        }

        $lamaran = Lamaran::with('pelamar', 'lowongan')->find($id);

        if (! $lamaran) {
            return response()->json(['message' => 'Lamaran tidak ditemukan.'], 404);
        }

        if ($lamaran->lowongan->user_id !== Auth::id()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'message' => 'Detail lamaran berhasil diambil.',
            'data'    => $lamaran,
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/lamaran/{id}/status",
     *     tags={"Pelamar"},
     *     summary="Update status lamaran pelamar (hanya Penyedia)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="ID Lamaran"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"menunggu","diproses","wawancara","diterima","ditolak"}, example="diproses"),
     *             @OA\Property(property="catatan_penyedia", type="string", nullable=true, example="Jadwal wawancara Senin pukul 09.00")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status lamaran berhasil diperbarui"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden — bukan penyedia"),
     *     @OA\Response(response=404, description="Lamaran tidak ditemukan")
     * )
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        if (Auth::user()->role !== 'penyedia') {
            return response()->json(['message' => 'Akses ditolak. Hanya untuk penyedia.'], 403);
        }

        $validated = $request->validate([
            'status'           => ['required', 'in:menunggu,diproses,wawancara,diterima,ditolak'],
            'catatan_penyedia' => ['nullable', 'string'],
        ]);

        $lamaran = Lamaran::with('lowongan')->find($id);

        if (! $lamaran) {
            return response()->json(['message' => 'Lamaran tidak ditemukan.'], 404);
        }

        if ($lamaran->lowongan->user_id !== Auth::id()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $lamaran->update($validated);

        return response()->json([
            'message' => 'Status lamaran berhasil diperbarui.',
            'data'    => $lamaran->load('pelamar', 'lowongan'),
        ]);
    }
}
