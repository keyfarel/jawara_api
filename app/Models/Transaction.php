<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_category_id',
        'billing_id',
        'title',
        'type',
        'amount',
        'transaction_date',
        'description',
        'proof_image'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function billing()
    {
        return $this->belongsTo(Billing::class);
    }
}
