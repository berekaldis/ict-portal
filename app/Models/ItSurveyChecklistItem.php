<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItSurveyChecklistItem extends Model
{
    use HasFactory;

    protected $table = 'it_survey_checklist_items';

    protected $fillable = [
        'category_id',
        'question',
        'help_text',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ItSurveyCategory::class, 'category_id');
    }
}
