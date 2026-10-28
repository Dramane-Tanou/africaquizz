<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class QuestionVersion extends Model
{
    use HasFactory, HasUuids;
    public $timestamps = false;
    protected $fillable = ['question_id', 'version_number', 'snapshot', 'created_by_type', 'created_by_id', 'created_at'];
    protected $casts = ['snapshot' => 'array', 'created_at' => 'datetime'];
    public function question() { return $this->belongsTo(Question::class); }
}