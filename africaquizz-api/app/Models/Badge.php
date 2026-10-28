<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Badge extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['code', 'name_fr', 'name_en', 'tier', 'rule_params'];
    protected $casts = ['rule_params' => 'array'];
    public function userBadges() { return $this->hasMany(UserBadge::class); }
}