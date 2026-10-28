<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionTranslation extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['question_id', 'locale', 'text', 'explanation', 'key_takeaway'];
    public function question() { return $this->belongsTo(Question::class); }
}