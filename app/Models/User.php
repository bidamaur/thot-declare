<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_FULL = 'full';
    public const ROLE_READ_ONLY = 'read_only';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_FULL,
        self::ROLE_READ_ONLY,
    ];

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

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isFullAccess(): bool
    {
        return $this->role === self::ROLE_FULL || $this->isAdmin();
    }

    public function isReadOnly(): bool
    {
        return $this->role === self::ROLE_READ_ONLY;
    }

    public function canModifyData(): bool
    {
        return !$this->isReadOnly();
    }
}
