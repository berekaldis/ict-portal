<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrQualification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'probation_start' => 'date:Y-m-d',
    ];

    public function internship()
    {
        return $this->belongsTo(HrInternshipRequest::class, 'internship_ref_id', 'request_id');
    }
}
