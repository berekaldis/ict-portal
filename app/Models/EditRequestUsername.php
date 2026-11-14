<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditRequestUsername extends Model
{
    use HasFactory;

    protected $table = 'edit_request_username';
    public $timestamps = false;

    protected $fillable = [
        'user_name',
        'branch',
        'request_count',
        'first_seen',
        'last_seen',
    ];

    protected $casts = [
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
        'request_count' => 'integer',
    ];

    public static function track(string $username, ?string $branch = null): void
    {
        $cleanUser = trim($username);
        if ($cleanUser === '') return;

        $record = static::where('user_name', $cleanUser)->first();
        if ($record) {
            $record->request_count += 1;
            if ($branch) {
                $record->branch = $branch;
            }
            $record->last_seen = now();
            $record->save();
        } else {
            static::create([
                'user_name' => $cleanUser,
                'branch' => $branch,
                'request_count' => 1,
                'first_seen' => now(),
                'last_seen' => now(),
            ]);
        }
    }
}
