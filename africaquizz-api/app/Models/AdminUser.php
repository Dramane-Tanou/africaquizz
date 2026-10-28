<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AdminUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = ['role_id', 'name', 'email', 'password', 'two_factor_secret', 'two_factor_enabled', 'last_login_at'];
    protected $hidden = ['password', 'two_factor_secret'];
    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role() { return $this->belongsTo(Role::class); }

    public function hasRole(string $roleName): bool
    {
        $this->loadMissing('role');
        return $this->role && $this->role->name === $roleName;
    }

    public function hasAnyRole(array $roleNames): bool
    {
        $this->loadMissing('role');
        return $this->role && in_array($this->role->name, $roleNames);
    }

    public function canValidate(): bool
    {
        return $this->hasAnyRole(['relecteur', 'admin', 'super_admin']);
    }

    public function canPublish(): bool
    {
        return $this->hasAnyRole(['editeur', 'admin', 'super_admin']);
    }

    public function canManageUsers(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    public function canCreate(): bool
    {
        return $this->hasAnyRole(['redacteur', 'editeur', 'admin', 'super_admin']);
    }
}