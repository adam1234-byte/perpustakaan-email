<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'nik', 'tanggal_lahir', 'alamat',
        'no_hp', 'foto_ktp', 'face_descriptor', 'otp_code', 'otp_expires_at', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token', 'otp_code', 'face_descriptor'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'tanggal_lahir' => 'date',
            'password' => 'hashed',
        ];
    }
}
