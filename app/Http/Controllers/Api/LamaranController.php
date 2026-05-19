<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LamaranController extends Controller
{
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

        if ($lowongan->batas_daftar->isPast()) {
            return response()->json(['message' => 'Batas pendaftaran lowongan sudah lewat.'], 422);
        }

        $alreadyApplied = Lamaran::where('user_id', Auth::id())
            ->where('lowongan_id', $validated['lowongan_id'])
            ->exists();

        if ($alreadyApplied) {
            return response()->json(['message' => 'Anda sudah melamar pada lowongan ini.'], 422);
        }

        $userId    = Auth::id();
        $uniqueId  = uniqid();

        $cvPath = $request->file('cv')->storeAs(
            'cv',
            "{$userId}_{$uniqueId}." . $request->file('cv')->getClientOriginalExtension(),
            'public'
        );

        $suratPath = null;
        if ($request->hasFile('surat_lamaran')) {
            $suratPath = $request->file('surat_lamaran')->storeAs(
                'surat',
                "{$userId}_{$uniqueId}." . $request->file('surat_lamaran')->getClientOriginalExtension(),
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
