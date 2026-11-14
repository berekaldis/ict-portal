<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrRecruitment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
    ];

    public function qualification()
    {
        return $this->belongsTo(HrQualification::class, 'qualification_ref_id', 'request_id');
    }
}
