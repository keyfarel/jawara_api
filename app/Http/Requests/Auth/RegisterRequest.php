<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // --- 1. User Account ---
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed', // Flutter min 6
            'phone'    => 'required|string|max:20',

            // --- 2. Citizen Data ---
            'full_name'     => 'required|string|max:255',
            'nik'           => 'required|string|size:16|unique:citizens,nik',
            'gender'        => 'required|in:male,female',
            'id_card_photo' => 'nullable|image|max:5120|required_with:selfie_photo',
            'selfie_photo'  => 'nullable|image|max:5120',
            'education'     => 'nullable|string',
            'occupation'    => 'nullable|string',

            // Field ini TIDAK dikirim Flutter saat register, jadi buat nullable
            'birth_place'   => 'nullable|string',
            'birth_date'    => 'nullable|date',
            'religion'      => 'nullable|string',
            'blood_type'    => 'nullable|in:A,B,AB,O',
            'family_role'   => 'nullable|string',

            // --- 3. Family Data ---
            // KK Number tidak dikirim Flutter, kita generate di Controller
            'kk_number'        => 'nullable|string',
            'ownership_status' => 'required|in:owner,renter,family,other', // Sesuaikan opsi flutter

            // --- 4. Housing ---
            // User bisa pilih ID rumah yg ada, ATAU isi alamat manual
            'house_id'      => 'nullable|exists:houses,id',
            'house_block'   => 'required_without:house_id|string|nullable',
            'house_number'  => 'required_without:house_id|string|nullable',
            'house_street'  => 'required_without:house_id|string|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'id_card_photo.required_with' =>
                'Foto KTP wajib diupload jika menggunakan foto selfie.',
        ];
    }
}
