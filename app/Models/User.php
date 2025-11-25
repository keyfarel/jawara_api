<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',                 // Baru
        'registration_status',  // Baru
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Relasi ---

    // Pengganti UserProfile: Data Warga linked ke User ini
    public function citizen()
    {
        return $this->hasOne(Citizen::class);
    }

    // User bisa mengirim banyak pesan aspirasi
    public function messages()
    {
        return $this->hasMany(CitizenMessage::class);
    }

    // User (Admin) bisa membuat banyak pengumuman
    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }

    // User mencatat banyak transaksi
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Log aktifitas user
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // --- JWT Methods ---
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'email' => $this->email
        ];
    }
}
