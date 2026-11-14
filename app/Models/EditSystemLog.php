<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditSystemLog extends Model
{
    use HasFactory;

    protected $table = 'edit_system_logs';
    public $timestamps = false;

    protected $fillable = [
        'timestamp',
        'action',
        'actor',
        'request_id',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public static function log(string $action, string $actor, ?string $requestId = null): static
    {
        return static::create([
            'timestamp' => now(),
            'action' => $action,
            'actor' => $actor,
            'request_id' => $requestId,
        ]);
    }
}
