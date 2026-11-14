<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrSystemConfig extends Model
{
    use HasFactory;

    protected $table = 'hr_system_config';
    protected $primaryKey = 'config_key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::where('config_key', $key)->value('config_value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['config_key' => $key], ['config_value' => $value]);
    }
}
