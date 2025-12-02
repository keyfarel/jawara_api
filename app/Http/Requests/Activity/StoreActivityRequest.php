<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Sesuaikan jika hanya Admin/Pengurus yang boleh
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'category'         => 'required|string|max:50',
            'activity_date'    => 'required|date',
            'location'         => 'required|string|max:255',
            'person_in_charge' => 'required|string|max:100',
            'description'      => 'nullable|string',
        ];
    }
}
