<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Role extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['name', 'label_fr', 'label_en'];
    public function adminUsers() { return $this->hasMany(AdminUser::class); }
}