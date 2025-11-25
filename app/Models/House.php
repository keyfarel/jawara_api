<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_name',
        'owner_name',
        'address',
        'house_type',
        'has_complete_facilities',
        'status'
    ];

    public function families()
    {
        return $this->hasMany(Family::class);
    }

    // Opsional: Melihat history mutasi di rumah ini
    public function mutations()
    {
        return $this->hasMany(Mutation::class);
    }
}
