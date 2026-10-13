<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isWali(): bool
    {
        return $this->role === 'wali';
    }

    public function isKepsek(): bool
    {
        return $this->role === 'kepsek';
    }

    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_id');
    }

    public function raportDibuat(): HasMany
    {
        return $this->hasMany(Raport::class, 'dibuat_oleh');
    }
}
