<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model
{
    use HasFactory, HasUuids;
    public $timestamps = false;
    protected $fillable = ['actor_type', 'actor_id', 'action', 'entity_type', 'entity_id', 'before', 'after', 'ip_address', 'user_agent', 'created_at'];
    protected $casts = ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
}