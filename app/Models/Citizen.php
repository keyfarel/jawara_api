<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Citizen extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'user_id',
        'nik',
        'name',
        'phone',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'blood_type',
        'id_card_photo',
        'family_role',
        'education',
        'occupation',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }
}
