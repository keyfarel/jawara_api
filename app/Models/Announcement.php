<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'image_url',
        'document_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
