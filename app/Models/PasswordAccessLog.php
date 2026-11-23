<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordAccessLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'password_access_logs';

    protected $fillable = [
        'entry_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function entry()
    {
        return $this->belongsTo(PasswordEntry::class, 'entry_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
