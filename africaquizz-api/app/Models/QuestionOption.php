<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['question_id', 'option_key', 'is_correct'];
    public function question() { return $this->belongsTo(Question::class); }
    public function translations() { return $this->hasMany(OptionTranslation::class, 'option_id'); }
}