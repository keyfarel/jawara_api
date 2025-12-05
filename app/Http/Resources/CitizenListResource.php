<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CitizenListResource extends JsonResource
{
    public function toArray($request)
    {
        // $this sekarang merujuk ke tabel 'citizens'
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nama' => $this->name, 
            'nik' => $this->nik,
            'jenis_kelamin' => $this->gender,
            'peran_keluarga' => $this->family_role,
            'no_hp' => $this->phone,
            
            'email' => optional($this->user)->email,
            'status_registrasi' => optional($this->user)->registration_status,
            
            'foto_identitas' => $this->id_card_photo 
                ? url('storage/' . $this->id_card_photo) 
                : null,
        ];
    }
}