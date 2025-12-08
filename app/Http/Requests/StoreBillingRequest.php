<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        return [
            'family_id'    => 'required|exists:families,id', // Mewakili "Nama Warga/KK"
            'dues_type_id' => 'required|exists:dues_types,id',
            'amount'       => 'required|numeric|min:0',      // "Jumlah Iuran"
            'period'       => 'required|string',             // "Tanggal/Periode" (misal: "Okt 2025")
            'status'       => 'in:paid,unpaid',
        ];
    }
}