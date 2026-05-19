<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="LamarIn API",
 *     description="REST API Sistem Informasi Lowongan Pekerjaan — Proyek Akhir Kelompok",
 *     @OA\Contact(email="kelompok@lamarIn.dev")
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Server Utama"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * ==========================================================================
 * SCHEMAS
 * ==========================================================================
 *
 * @OA\Schema(
 *     schema="KategoriPekerjaan",
 *     type="object",
 *     title="Kategori Pekerjaan",
 *     @OA\Property(property="id",             type="integer", example=1),
 *     @OA\Property(property="nama_kategori",  type="string",  example="Teknologi Informasi"),
 *     @OA\Property(property="deskripsi",      type="string",  nullable=true, example="Meliputi posisi software engineer, sysadmin, dll."),
 *     @OA\Property(property="created_at",     type="string",  format="date-time"),
 *     @OA\Property(property="updated_at",     type="string",  format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Lowongan",
 *     type="object",
 *     title="Lowongan Pekerjaan",
 *     @OA\Property(property="id",              type="integer", example=10),
 *     @OA\Property(property="user_id",         type="integer", example=2),
 *     @OA\Property(property="kategori_id",     type="integer", nullable=true, example=1),
 *     @OA\Property(property="judul",           type="string",  example="Backend Engineer Laravel"),
 *     @OA\Property(property="deskripsi",       type="string",  example="Kami mencari backend engineer berpengalaman..."),
 *     @OA\Property(property="kualifikasi",     type="string",  example="Min. S1 Informatika, pengalaman 1 tahun"),
 *     @OA\Property(property="lokasi",          type="string",  example="Surabaya"),
 *     @OA\Property(property="jenis_pekerjaan", type="string",  enum={"full-time","part-time","remote","kontrak"}, example="full-time"),
 *     @OA\Property(property="gaji_min",        type="integer", nullable=true, example=5000000),
 *     @OA\Property(property="gaji_max",        type="integer", nullable=true, example=12000000),
 *     @OA\Property(property="batas_daftar",    type="string",  format="date", example="2026-06-30"),
 *     @OA\Property(property="status",          type="string",  enum={"aktif","nonaktif"}, example="aktif"),
 *     @OA\Property(property="created_at",      type="string",  format="date-time"),
 *     @OA\Property(property="updated_at",      type="string",  format="date-time"),
 *     @OA\Property(property="kategori",        ref="#/components/schemas/KategoriPekerjaan", nullable=true),
 *     @OA\Property(
 *         property="penyedia",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id",   type="integer", example=2),
 *         @OA\Property(property="name", type="string",  example="PT Maju Bersama")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ErrorValidasi",
 *     type="object",
 *     @OA\Property(property="status",  type="boolean", example=false),
 *     @OA\Property(property="message", type="string",  example="Validasi gagal."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\AdditionalProperties(
 *             type="array",
 *             @OA\Items(type="string")
 *         ),
 *         example={"nama_kategori": {"Nama kategori wajib diisi."}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ErrorUnauthorized",
 *     type="object",
 *     @OA\Property(property="status",  type="boolean", example=false),
 *     @OA\Property(property="message", type="string",  example="Token tidak valid atau sudah kedaluwarsa.")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorForbidden",
 *     type="object",
 *     @OA\Property(property="status",  type="boolean", example=false),
 *     @OA\Property(property="message", type="string",  example="Akses ditolak. Hanya Penyedia yang dapat melakukan aksi ini.")
 * )
 */
class SwaggerController extends Controller
{
    // Controller ini kosong — hanya sebagai tempat anotasi OpenAPI global.
    // Tidak perlu didaftarkan ke route.
}