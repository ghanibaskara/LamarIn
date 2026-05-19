<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreKategoriRequest extends FormRequest
{
    /**
     * Menentukan apakah user berhak membuat request ini.
     * Otorisasi role sudah ditangani oleh middleware CheckRole di routing,
     * sehingga di sini kita cukup pastikan user sudah login (terautentikasi).
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Aturan validasi untuk menyimpan kategori baru.
     */
    public function rules(): array
    {
        return [
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                'unique:kategori_pekerjaans,nama_kategori', // harus unik di seluruh tabel
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
     * Override handler validasi gagal.
     * Mengembalikan JSON konsisten alih-alih redirect (penting untuk REST API).
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