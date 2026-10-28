<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category_id', 'type', 'difficulty', 'time_limit_s', 'is_sensitive', 'pending_reports_count', 'last_reported_at',
        'status', 'external_id', 'created_by_type', 'created_by_id',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function translations()
    {
        return $this->hasMany(QuestionTranslation::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'question_countries');
    }

    public function sources()
    {
        return $this->hasMany(QuestionSource::class);
    }
}