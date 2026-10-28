<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class QuestionReview extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['question_id', 'reviewer_type', 'reviewer_id', 'checklist', 'decision', 'comment'];
    protected $casts = ['checklist' => 'array'];
    public function question() { return $this->belongsTo(Question::class); }
}