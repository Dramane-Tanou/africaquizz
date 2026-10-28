<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionTranslation extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['option_id', 'locale', 'text'];
    public function option() { return $this->belongsTo(QuestionOption::class, 'option_id'); }
}