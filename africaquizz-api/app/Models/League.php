<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class League extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['week_key','tier','starts_at','ends_at'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    public function members() { return $this->hasMany(LeagueMember::class); }

    const TIERS = ['bronze', 'argent', 'or', 'diamant'];
    const PROMOTION_COUNT = 5;
    const RELEGATION_COUNT = 5;
}