<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseItem extends Model
{
    use HasFactory;

    protected $table = 'case_cases';
    public $timestamps = false;

    protected $fillable = [
        'case_id',
        'case_type',
        'description',
        'created_date',
        'due_date',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'remarks',
        'attachments',
        'last_modified',
    ];

    protected $casts = [
        'created_date' => 'date',
        'due_date' => 'date',
        'last_modified' => 'datetime',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class, 'case_id', 'case_id')->orderBy('created_at', 'desc');
    }

    public function history(): HasMany
    {
        return $this->hasMany(CaseHistory::class, 'case_id', 'case_id')->orderBy('timestamp', 'desc');
    }
}
