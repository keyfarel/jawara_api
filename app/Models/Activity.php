<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'name',
        'category',
        'activity_date',
        'location',
        'person_in_charge',
        'description',
        'status'
    ];

    protected $casts = [
        'activity_date' => 'datetime'
    ];
}
