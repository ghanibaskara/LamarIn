<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Lamaran",
 *     description="Fitur Pelamaran oleh Pelamar"
 * )
 */
class LamaranController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/lamaran",
     *     tags={"Lamaran"},
     *     summary="Kirim lamaran pada lowongan (hanya Pelamar)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"lowongan_id","cv"},
     *                 @OA\Property(property="lowongan_id", type="integer", example=1),
     *                 @OA\Property(property="cv", type="string", format="binary", description="File CV (PDF/DOC/DOCX, maks 2MB)"),
     *                 @OA\Property(property="surat_lamaran", type="string", format="binary", description="Surat lamaran (opsional, PDF/DOC/DOCX, maks 2MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Lamaran berhasil dikirim"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden — bukan pelamar"),
     *     @OA\Response(response=422, description="Validasi gagal atau lowongan tidak aktif")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        if (Auth::user()->role !== 'pelamar') {
            return response()->json(['message' => 'Akses ditolak. Hanya untuk pelamar.'], 403);
        }

        $validated = $request->validate([
            'lowongan_id'   => ['required', 'integer', 'exists:lowongans,id'],
            'cv'            => ['required', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
            'surat_lamaran' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
        ]);

        $lowongan = Lowongan::find($validated['lowongan_id']);

        if ($lowongan->status !== 'aktif') {
            return response()->json(['message' => 'Lowongan ini tidak sedang aktif.'], 422);
        }

        $alreadyApplied = Lamaran::where('user_id', Auth::id())
            ->where('lowongan_id', $validated['lowongan_id'])
            ->exists();

        if ($alreadyApplied) {
            return response()->json(['message' => 'Anda sudah melamar pada lowongan ini.'], 422);
        }

        $userId    = Auth::id();
        $timestamp = now()->timestamp;

        $cvPath = $request->file('cv')->storeAs(
            'cv',
            "{$userId}_{$timestamp}." . $request->file('cv')->getClientOriginalExtension(),
            'public'
        );

        $suratPath = null;
        if ($request->hasFile('surat_lamaran')) {
            $suratPath = $request->file('surat_lamaran')->storeAs(
                'surat',
                "{$userId}_{$timestamp}." . $request->file('surat_lamaran')->getClientOriginalExtension(),
                'public'
            );
        }

        $lamaran = Lamaran::create([
            'user_id'            => $userId,
            'lowongan_id'        => $validated['lowongan_id'],
            'cv_path'            => $cvPath,
            'surat_lamaran_path' => $suratPath,
            'status'             => 'menunggu',
        ]);

        return response()->json([
            'message' => 'Lamaran berhasil dikirim.',
            'data'    => $lamaran->load('lowongan'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/lamaran",
     *     tags={"Lamaran"},
     *     summary="Riwayat semua lamaran milik pelamar yang login",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Daftar lamaran berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden — bukan pelamar")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Pastikan hanya pelamar yang bisa melihat riwayat lamarannya
        if (Auth::user()->role !== 'pelamar') {
            return response()->json(['message' => 'Akses ditolak. Hanya untuk pelamar.'], 403);
        }

        // Ambil semua data lamaran milik user yang sedang login, beserta detail lowongannya
        $lamarans = Lamaran::with('lowongan')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Berhasil mengambil riwayat status lamaran.',
            'data'    => $lamarans,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/lamaran/{id}",
     *     tags={"Lamaran"},
     *     summary="Batalkan lamaran (hanya jika status masih 'menunggu')",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lamaran berhasil dibatalkan"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lamaran tidak ditemukan"),
     *     @OA\Response(response=422, description="Lamaran tidak dapat dibatalkan karena sudah diproses")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        if (Auth::user()->role !== 'pelamar') {
            return response()->json(['message' => 'Akses ditolak. Hanya untuk pelamar.'], 403);
        }

        $lamaran = Lamaran::find($id);

        if (! $lamaran) {
            return response()->json(['message' => 'Lamaran tidak ditemukan.'], 404);
        }

        if ($lamaran->user_id !== Auth::id()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($lamaran->status !== 'menunggu') {
            return response()->json(['message' => 'Lamaran tidak dapat dibatalkan karena sudah diproses.'], 422);
        }

        Storage::disk('public')->delete($lamaran->cv_path);

        if ($lamaran->surat_lamaran_path) {
            Storage::disk('public')->delete($lamaran->surat_lamaran_path);
        }

        $lamaran->delete();

        return response()->json([
            'message' => 'Lamaran berhasil dibatalkan.',
        ]);
    }
}
