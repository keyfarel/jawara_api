<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mutation extends Model
{
    protected $fillable = [
        'family_id',
        'house_id',
        'mutation_type',
        'mutation_date',
        'reason'
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }
}
