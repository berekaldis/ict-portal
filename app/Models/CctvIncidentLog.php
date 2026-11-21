<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CctvIncidentLog extends Model
{
    use HasFactory;

    protected $table = 'cctv_incident_logs';

    protected $fillable = [
        'incident_number',
        'incident_date',
        'branch_id',
        'branch_name',
        'device_name',
        'issue_type',
        'severity',
        'impact',
        'first_check_done',
        'reported_by',
        'reported_at',
        'fixed_at',
        'status',
        'action_taken',
        'telegram_announced_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'telegram_announced_at' => 'datetime',
    ];
}
