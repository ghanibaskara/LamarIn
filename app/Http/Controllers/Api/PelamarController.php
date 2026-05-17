<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PelamarController extends Controller
{
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
