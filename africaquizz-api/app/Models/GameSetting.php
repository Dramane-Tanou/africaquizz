<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSetting extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['key', 'value'];
    protected $casts = ['value' => 'array'];
}