<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDuesTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Pastikan user terautentikasi JWT nanti di route
    }

    public function rules(): array
    {
        return [
            'name'   => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ];
    }
}