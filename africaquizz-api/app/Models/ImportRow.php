<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ImportRow extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['import_id', 'row_number', 'raw_data', 'normalized_data', 'errors', 'status', 'question_id'];
    protected $casts = ['raw_data' => 'array', 'normalized_data' => 'array', 'errors' => 'array'];
    public function import() { return $this->belongsTo(Import::class); }
    public function question() { return $this->belongsTo(Question::class); }
}