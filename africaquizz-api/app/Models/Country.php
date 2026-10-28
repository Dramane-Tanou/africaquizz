<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['region_id', 'iso2', 'iso3', 'slug', 'capital', 'status'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }
}
