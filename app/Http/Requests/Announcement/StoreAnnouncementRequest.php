<?php

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Biasanya hanya Admin/Pengurus yg boleh broadcast
        // return auth()->user()->role === 'admin';
        return true;
    }

    public function rules(): array
    {
        return [
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            // Validasi Gambar (Max 2MB)
            'image'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            // Validasi Dokumen (PDF, Word - Max 5MB)
            'document' => 'nullable|mimes:pdf,doc,docx|max:5120',
        ];
    }
}
