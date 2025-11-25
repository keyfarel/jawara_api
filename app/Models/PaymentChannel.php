<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentChannel extends Model
{
    protected $fillable = [
        'channel_name',
        'type',
        'account_number',
        'account_name',
        'thumbnail',
        'qr_code',
        'notes',
        'is_active'
    ];
}
