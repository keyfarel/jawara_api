<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    protected $fillable = [
        'family_id',
        'dues_type_id',
        'billing_code',
        'period',
        'amount',
        'status'
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function duesType()
    {
        return $this->belongsTo(DuesType::class);
    }

    // Tagihan bisa dibayar (memiliki transaksi)
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
