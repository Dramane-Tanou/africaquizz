<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PushSubscription extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['user_id','guest_session_id','endpoint','auth_token','public_key','content_encoding','user_agent','last_used_at'];
    protected $casts = ['last_used_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}