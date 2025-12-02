<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // <--- Jangan lupa import ini

class Announcement extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'image_url',     // Path database
        'document_url'   // Path database
    ];

    // Eager load URL virtual setiap kali model dipanggil
    protected $appends = ['image_link', 'document_link'];
    // Sembunyikan path mentah agar rapi
    protected $hidden = ['image_url', 'document_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- ACCESSOR ---

    // Mengubah 'announcements/foto.jpg' jadi 'http://localhost/storage/announcements/foto.jpg'
    public function getImageLinkAttribute()
    {
        return $this->image_url ? url('storage/' . $this->image_url) : null;
    }

    public function getDocumentLinkAttribute()
    {
        return $this->document_url ? url('storage/' . $this->document_url) : null;
    }
}
