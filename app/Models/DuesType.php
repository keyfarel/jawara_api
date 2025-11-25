<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuesType extends Model
{
    protected $fillable = ['name', 'amount'];

    public function billings()
    {
        return $this->hasMany(Billing::class);
    }
}
