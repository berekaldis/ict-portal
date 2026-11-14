<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'q4_orders' => 'array',
        'q6_factors' => 'array',
        'q9_improvements' => 'array',
        'q15_waiter_improvements' => 'array',
        'q17_interior_improvements' => 'array',
    ];
}
