<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CctvBranchRegister extends Model
{
    use HasFactory;

    protected $table = 'cctv_branch_registers';

    protected $fillable = [
        'branch_no',
        'branch_id',
        'branch_name',
        'nvr_model_ip',
        'camera_count',
        'hdd_size_days',
        'firmware',
        'last_check',
        'status',
        'issues',
        'notes',
    ];

    protected $casts = [
        'branch_no' => 'integer',
        'camera_count' => 'integer',
        'last_check' => 'date',
    ];
}
