<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['token', 'ip_address', 'user_agent', 'last_active_at'];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    public function consents()
    {
        return $this->hasMany(Consent::class);
    }
}
