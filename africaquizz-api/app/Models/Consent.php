<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'guest_session_id', 'document_type', 'version', 'accepted_at'];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guestSession()
    {
        return $this->belongsTo(GuestSession::class);
    }
}
