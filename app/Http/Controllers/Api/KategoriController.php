<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKategoriRequest;
use App\Http\Requests\UpdateKategoriRequest;
use App\Models\KategoriPekerjaan;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Kategori",
 *     description="Manajemen Kategori Pekerjaan"
 * )
 */
class KategoriController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /api/kategori — Public (semua role, termasuk tanpa login)
    // -------------------------------------------------------------------------

    /**
     * @OA\Get(
     *     path="/api/kategori",
     *     tags={"Kategori"},
     *     summary="Tampilkan semua kategori pekerjaan",
     *     description="Endpoint publik. Tidak memerlukan autentikasi.",
     *     @OA\Response(
     *         response=200,
     *         description="Daftar kategori berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Daftar kategori berhasil diambil."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/KategoriPekerjaan")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $kategoris = KategoriPekerjaan::orderBy('nama_kategori')->get();

        return response()->json([
            'message' => 'Daftar kategori berhasil diambil.',
            'data'    => $kategoris,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/kategori — Hanya Penyedia
    // -------------------------------------------------------------------------

    /**
     * @OA\Post(
     *     path="/api/kategori",
     *     tags={"Kategori"},
     *     summary="Buat kategori pekerjaan baru",
     *     description="Hanya dapat diakses oleh pengguna dengan role **penyedia**.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama_kategori"},
     *             @OA\Property(property="nama_kategori", type="string", maxLength=100, example="Teknologi Informasi"),
     *             @OA\Property(property="deskripsi", type="string", nullable=true, example="Lowongan di bidang IT, software, dan jaringan.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Kategori berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Kategori berhasil dibuat."),
     *             @OA\Property(property="data", ref="#/components/schemas/KategoriPekerjaan")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Token tidak valid atau tidak ditemukan"),
     *     @OA\Response(response=403, description="Akses ditolak. Bukan Penyedia."),
     *     @OA\Response(response=422, description="Validasi gagal (nama_kategori duplikat atau kosong)")
     * )
     */
    public function store(StoreKategoriRequest $request): JsonResponse
    {
        $kategori = KategoriPekerjaan::create($request->validated());

        return response()->json([
            'message' => 'Kategori berhasil dibuat.',
            'data'    => $kategori,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // PUT /api/kategori/{id} — Hanya Penyedia
    // -------------------------------------------------------------------------

    /**
     * @OA\Put(
     *     path="/api/kategori/{id}",
     *     tags={"Kategori"},
     *     summary="Perbarui kategori pekerjaan",
     *     description="Hanya dapat diakses oleh pengguna dengan role **penyedia**.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID kategori yang akan diperbarui",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama_kategori"},
     *             @OA\Property(property="nama_kategori", type="string", maxLength=100, example="Desain Grafis"),
     *             @OA\Property(property="deskripsi", type="string", nullable=true, example="Lowongan untuk desainer visual.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kategori berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Kategori berhasil diperbarui."),
     *             @OA\Property(property="data", ref="#/components/schemas/KategoriPekerjaan")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Token tidak valid"),
     *     @OA\Response(response=403, description="Akses ditolak"),
     *     @OA\Response(response=404, description="Kategori tidak ditemukan"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function update(UpdateKategoriRequest $request, int $id): JsonResponse
    {
        $kategori = KategoriPekerjaan::find($id);

        if (! $kategori) {
            return response()->json([
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        $kategori->update($request->validated());

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data'    => $kategori->fresh(),
        ]);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/kategori/{id} — Hanya Penyedia
    // -------------------------------------------------------------------------

    /**
     * @OA\Delete(
     *     path="/api/kategori/{id}",
     *     tags={"Kategori"},
     *     summary="Hapus kategori pekerjaan",
     *     description="Hanya dapat diakses oleh pengguna dengan role **penyedia**. Lowongan terkait akan memiliki kategori_id = NULL (SET NULL).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID kategori yang akan dihapus",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kategori berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Kategori berhasil dihapus.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Token tidak valid"),
     *     @OA\Response(response=403, description="Akses ditolak"),
     *     @OA\Response(response=404, description="Kategori tidak ditemukan")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $kategori = KategoriPekerjaan::find($id);

        if (! $kategori) {
            return response()->json([
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}