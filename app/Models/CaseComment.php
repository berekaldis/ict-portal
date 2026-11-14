<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseComment extends Model
{
    use HasFactory;

    protected $table = 'case_comments';
    public $timestamps = false;

    protected $fillable = [
        'case_id',
        'author',
        'comment',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseItem::class, 'case_id', 'case_id');
    }
}
