<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItSurveyCategory extends Model
{
    use HasFactory;

    protected $table = 'it_survey_categories';

    protected $fillable = [
        'name',
        'icon',
        'type',
        'has_photo',
        'description',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'has_photo' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(ItSurveyChecklistItem::class, 'category_id')->orderBy('display_order');
    }
}
