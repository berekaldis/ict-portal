<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseHistory extends Model
{
    use HasFactory;

    protected $table = 'case_history';
    public $timestamps = false;

    protected $fillable = [
        'case_id',
        'action',
        'details',
        'user',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseItem::class, 'case_id', 'case_id');
    }
}
