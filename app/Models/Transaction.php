<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_category_id', 'billing_id',
        'title', 'type', 'amount', 'transaction_date',
        'description', 'proof_image'
    ];

    // Eager load URL gambar bukti
    protected $appends = ['proof_image_link'];
    protected $hidden = ['proof_image'];

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function getProofImageLinkAttribute()
    {
        return $this->proof_image ? url('storage/' . $this->proof_image) : null;
    }
}
