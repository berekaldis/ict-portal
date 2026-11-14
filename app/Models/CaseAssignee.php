<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseAssignee extends Model
{
    use HasFactory;

    protected $table = 'case_assignees';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'telegram_id',
        'status',
    ];
}
