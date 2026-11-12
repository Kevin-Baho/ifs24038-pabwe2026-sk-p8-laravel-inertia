<?php

namespace App\Features\Post\Requests;

use App\Features\Post\Enums\PostCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dipakai bersama oleh store & update; otorisasi kepemilikan ditangani PostPolicy di controller. */
class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(PostCategory::values())],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_cover' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul artikel wajib diisi.',
            'category.required' => 'Pilih salah satu kategori.',
            'category.in' => 'Kategori tidak valid.',
            'content.required' => 'Konten artikel tidak boleh kosong.',
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.max' => 'Ukuran cover maksimal 4 MB (batas payload Vercel 4.5 MB).',
        ];
    }
}
