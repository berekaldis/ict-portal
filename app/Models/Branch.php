<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';

    protected $fillable = [
        'id',
        'branch_name',
        'is_active',
        'telegram_chat_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['name'];

    public function getNameAttribute(): string
    {
        return $this->branch_name ?? '';
    }
}
