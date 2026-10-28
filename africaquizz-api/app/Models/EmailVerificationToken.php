<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class EmailVerificationToken extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['user_id', 'token', 'expires_at', 'used_at'];
    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}