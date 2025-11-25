<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionCategory extends Model
{
    protected $fillable = ['name', 'type']; // type: income/expense

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
