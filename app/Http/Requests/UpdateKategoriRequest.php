<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateKategoriRequest extends FormRequest
{
    /**
     * Menentukan apakah user berhak membuat request ini.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Aturan validasi untuk memperbarui kategori.
     * 
     * KRITIS — Rule::unique()->ignore():
     * Ketika update, aturan unique HARUS mengabaikan baris milik dirinya sendiri.
     * Tanpa ini, update tanpa mengubah nama_kategori akan selalu gagal validasi.
     * 
     * $this->route('id') mengambil nilai {id} dari URL /api/kategori/{id}.
     */
    public function rules(): array
    {
        // Ambil ID kategori dari parameter route
        $kategoriId = $this->route('id');

        return [
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori_pekerjaans', 'nama_kategori')
                    ->ignore($kategoriId), // abaikan baris dengan id ini saat cek unique
            ],
            'deskripsi' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Pesan error kustom dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.string'   => 'Nama kategori harus berupa teks.',
            'nama_kategori.max'      => 'Nama kategori maksimal 100 karakter.',
            'nama_kategori.unique'   => 'Nama kategori sudah digunakan, gunakan nama lain.',
            'deskripsi.string'       => 'Deskripsi harus berupa teks.',
        ];
    }

    /**
     * Override handler validasi gagal → kembalikan JSON.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Override handler otorisasi gagal.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.',
            ], 403)
        );
    }
}