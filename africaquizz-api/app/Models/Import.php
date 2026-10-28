<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Import extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['user_id', 'uploaded_by_admin_id', 'original_filename', 'stored_path', 'mime_type', 'size_bytes', 'status', 'totals', 'error_message', 'completed_at'];
    protected $casts = ['totals' => 'array', 'completed_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function rows() { return $this->hasMany(ImportRow::class); }
}