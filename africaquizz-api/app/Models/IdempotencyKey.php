<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class IdempotencyKey extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['key', 'endpoint', 'method', 'user_id', 'response_body', 'response_status', 'expires_at'];
    protected $casts = ['response_body' => 'array', 'expires_at' => 'datetime'];
}