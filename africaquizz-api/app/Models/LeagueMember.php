<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class LeagueMember extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['league_id','user_id','xp_earned','final_rank','promoted','relegated'];
    protected $casts = ['promoted' => 'boolean', 'relegated' => 'boolean'];
    public function league() { return $this->belongsTo(League::class); }
    public function user() { return $this->belongsTo(User::class); }
}