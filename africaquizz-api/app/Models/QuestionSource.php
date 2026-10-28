<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class QuestionSource extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['question_id', 'source_type', 'publisher', 'title', 'url', 'accessed_at', 'link_status'];
    public function question() { return $this->belongsTo(Question::class); }
}