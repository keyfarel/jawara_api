<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refresh_token' => 'required|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'refresh_token.required' => 'Refresh token wajib diisi.',
            'refresh_token.uuid'     => 'Refresh token harus berupa UUID yang valid.',
        ];
    }
}
