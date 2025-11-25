<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_id',
        'kk_number',
        'ownership_status', // Baru
        'status',
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function citizens()
    {
        return $this->hasMany(Citizen::class);
    }

    // Satu keluarga punya banyak tagihan
    public function billings()
    {
        return $this->hasMany(Billing::class);
    }

    // Riwayat mutasi keluarga (pindah masuk/keluar)
    public function mutations()
    {
        return $this->hasMany(Mutation::class);
    }
}
