<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Media extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'filename','path','disk','mime_type','width','height','size_bytes','hash','blurhash',
        'license','author','source_url','attribution_text','alt_text_fr','alt_text_en','uploaded_by_admin_id',
    ];
    public function uploader() { return $this->belongsTo(AdminUser::class, 'uploaded_by_admin_id'); }
}