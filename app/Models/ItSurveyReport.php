<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItSurveyReport extends Model
{
    use HasFactory;

    protected $table = 'it_survey_reports';

    protected $fillable = [
        'report_uuid',
        'branch_id',
        'branch_name',
        'technician_name',
        'technician_id',
        'branch_manager',
        'type',
        'health_score',
        'health_status',
        'critical_count',
        'total_items',
        'passed_items',
        'failed_items',
        'skipped_items',
        'checklist_data',
        'issues_summary',
        'actions_taken',
        'recommendations',
        'photos',
        'submitted_at',
    ];

    protected $casts = [
        'checklist_data' => 'array',
        'photos' => 'array',
        'submitted_at' => 'datetime',
    ];
}
