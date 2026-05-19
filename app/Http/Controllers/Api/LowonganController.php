<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lowongan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Lowongan",
 *     description="Manajemen Lowongan Pekerjaan oleh Penyedia"
 * )
 */
class LowonganController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/lowongan",
     *     tags={"Lowongan"},
     *     summary="Melihat lowongan (penyedia: milik sendiri | pelamar: semua aktif + filter)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="kategori_id", in="query", required=false,
     *         @OA\Schema(type="integer"), description="Filter berdasarkan ID kategori"),
     *     @OA\Parameter(name="lokasi", in="query", required=false,
     *         @OA\Schema(type="string"), description="Filter lokasi (partial match)"),
     *     @OA\Parameter(name="jenis_pekerjaan", in="query", required=false,
     *         @OA\Schema(type="string", enum={"full-time","part-time","remote","kontrak"}),
     *         description="Filter jenis pekerjaan"),
     *     @OA\Parameter(name="keyword", in="query", required=false,
     *         @OA\Schema(type="string"), description="Cari di judul dan deskripsi"),
     *     @OA\Response(response=200, description="Daftar lowongan berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role === 'penyedia') {
            $lowongans = Lowongan::with('kategori')
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        } else {
            $query = Lowongan::with('kategori', 'penyedia')
                ->where('status', 'aktif');

            $query->when($request->filled('kategori_id'), function ($q) use ($request) {
                $q->where('kategori_id', (int) $request->kategori_id);
            });

            $query->when($request->filled('lokasi'), function ($q) use ($request) {
                $q->whereRaw('LOWER(lokasi) LIKE ?', ['%' . strtolower($request->lokasi) . '%']);
            });

            $query->when($request->filled('jenis_pekerjaan'), function ($q) use ($request) {
                $q->whereRaw('LOWER(jenis_pekerjaan) = ?', [strtolower($request->jenis_pekerjaan)]);
            });

            $query->when($request->filled('keyword'), function ($q) use ($request) {
                $keyword = '%' . strtolower($request->keyword) . '%';
                $q->where(function ($inner) use ($keyword) {
                    $inner->whereRaw('LOWER(judul) LIKE ?', [$keyword])
                          ->orWhereRaw('LOWER(deskripsi) LIKE ?', [$keyword]);
                });
            });

            $lowongans = $query->latest()->get();
        }

        return response()->json([
            'message' => 'Daftar lowongan berhasil diambil.',
            'data'    => $lowongans,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/lowongan",
     *     tags={"Lowongan"},
     *     summary="Membuat lowongan baru (hanya Penyedia)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"judul","deskripsi","kualifikasi","lokasi","jenis_pekerjaan","batas_daftar"},
     *             @OA\Property(property="judul", type="string", example="Backend Developer"),
     *             @OA\Property(property="deskripsi", type="string", example="Membangun REST API"),
     *             @OA\Property(property="kualifikasi", type="string", example="Min. S1 Informatika"),
     *             @OA\Property(property="lokasi", type="string", example="Malang"),
     *             @OA\Property(property="jenis_pekerjaan", type="string", enum={"full-time","part-time","remote","kontrak"}),
     *             @OA\Property(property="gaji_min", type="integer", example=5000000),
     *             @OA\Property(property="gaji_max", type="integer", example=10000000),
     *             @OA\Property(property="batas_daftar", type="string", format="date", example="2026-06-30"),
     *             @OA\Property(property="kategori_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Lowongan berhasil dibuat"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden — bukan penyedia"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        if (Auth::user()->role !== 'penyedia') {
            return response()->json(['message' => 'Akses ditolak. Hanya untuk penyedia.'], 403);
        }

        $validated = $request->validate([
            'judul'           => ['required', 'string', 'max:255'],
            'deskripsi'       => ['required', 'string'],
            'kualifikasi'     => ['required', 'string'],
            'lokasi'          => ['required', 'string', 'max:255'],
            'jenis_pekerjaan' => ['required', 'in:full-time,part-time,remote,kontrak'],
            'gaji_min'        => ['nullable', 'integer', 'min:0'],
            'gaji_max'        => ['nullable', 'integer', 'min:0', 'gte:gaji_min'],
            'batas_daftar'    => ['required', 'date', 'after:today'],
            'kategori_id'     => ['nullable', 'exists:kategori_pekerjaans,id'],
        ]);

        $lowongan = Lowongan::create([
            ...$validated,
            'user_id' => Auth::id(),
            'status'  => 'aktif',
        ]);

        return response()->json([
            'message' => 'Lowongan berhasil dibuat.',
            'data'    => $lowongan->load('kategori'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/lowongan/{id}",
     *     tags={"Lowongan"},
     *     summary="Melihat detail satu lowongan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detail lowongan"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lowongan tidak ditemukan")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $user     = Auth::user();
        $lowongan = Lowongan::with('kategori', 'penyedia')->find($id);

        if (! $lowongan) {
            return response()->json(['message' => 'Lowongan tidak ditemukan.'], 404);
        }

        if ($user->role === 'penyedia') {
            if ($lowongan->user_id !== Auth::id()) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }
        } else {
            if ($lowongan->status !== 'aktif') {
                return response()->json(['message' => 'Lowongan tidak ditemukan.'], 404);
            }
        }

        return response()->json([
            'message' => 'Detail lowongan berhasil diambil.',
            'data'    => $lowongan,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/lowongan/{id}",
     *     tags={"Lowongan"},
     *     summary="Memperbarui informasi lowongan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="judul", type="string"),
     *             @OA\Property(property="deskripsi", type="string"),
     *             @OA\Property(property="kualifikasi", type="string"),
     *             @OA\Property(property="lokasi", type="string"),
     *             @OA\Property(property="jenis_pekerjaan", type="string", enum={"full-time","part-time","remote","kontrak"}),
     *             @OA\Property(property="gaji_min", type="integer"),
     *             @OA\Property(property="gaji_max", type="integer"),
     *             @OA\Property(property="batas_daftar", type="string", format="date"),
     *             @OA\Property(property="kategori_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Lowongan berhasil diperbarui"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lowongan tidak ditemukan")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
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

        $validated = $request->validate([
            'judul'           => ['sometimes', 'string', 'max:255'],
            'deskripsi'       => ['sometimes', 'string'],
            'kualifikasi'     => ['sometimes', 'string'],
            'lokasi'          => ['sometimes', 'string', 'max:255'],
            'jenis_pekerjaan' => ['sometimes', 'in:full-time,part-time,remote,kontrak'],
            'gaji_min'        => ['nullable', 'integer', 'min:0'],
            'gaji_max'        => array_filter(['nullable', 'integer', 'min:0', $request->has('gaji_min') ? 'gte:gaji_min' : null]),
            'batas_daftar'    => ['sometimes', 'date', 'after:today'],
            'kategori_id'     => ['nullable', 'exists:kategori_pekerjaans,id'],
        ]);

        $lowongan->update($validated);

        return response()->json([
            'message' => 'Lowongan berhasil diperbarui.',
            'data'    => $lowongan->fresh()->load('kategori'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/lowongan/{id}",
     *     tags={"Lowongan"},
     *     summary="Menghapus lowongan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lowongan berhasil dihapus"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lowongan tidak ditemukan")
     * )
     */
    public function destroy(string $id): JsonResponse
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

        $lowongan->delete();

        return response()->json([
            'message' => 'Lowongan berhasil dihapus.',
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/lowongan/{id}/status",
     *     tags={"Lowongan"},
     *     summary="Mengubah status aktif/nonaktif lowongan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"aktif","nonaktif"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status lowongan berhasil diubah"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lowongan tidak ditemukan")
     * )
     */
    public function updateStatus(Request $request, string $id): JsonResponse
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

        $validated = $request->validate([
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        $lowongan->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status lowongan berhasil diubah.',
            'data'    => $lowongan->fresh(),
        ]);
    }
}