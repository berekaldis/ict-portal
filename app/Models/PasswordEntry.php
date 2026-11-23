<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PasswordEntry extends Model
{
    use HasFactory;

    protected $table = 'password_entries';

    protected $fillable = [
        'title',
        'category',
        'device_type',
        'ip_address',
        'port',
        'link_url',
        'protocol',
        'username',
        'password',
        'secondary_secret',
        'auth_type',
        'branch_name',
        'environment',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
        'last_revealed_at',
        'last_revealed_by',
    ];

    /**
     * Hidden fields to prevent accidental exposure in API or serialization.
     */
    protected $hidden = [
        'password',
        'secondary_secret',
    ];

    protected $appends = [
        'has_password',
        'has_secondary_secret',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_active' => 'boolean',
        'last_revealed_at' => 'datetime',
    ];

    /**
     * Automatically encrypt password when setting.
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt password safely.
     */
    public function getDecryptedPassword()
    {
        try {
            return Crypt::decryptString($this->attributes['password']);
        } catch (\Throwable $e) {
            return 'Error decrypting password';
        }
    }

    /**
     * Automatically encrypt secondary secret when setting.
     */
    public function setSecondarySecretAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['secondary_secret'] = Crypt::encryptString($value);
        } else {
            $this->attributes['secondary_secret'] = null;
        }
    }

    /**
     * Decrypt secondary secret safely.
     */
    public function getDecryptedSecondarySecret()
    {
        if (empty($this->attributes['secondary_secret'])) {
            return null;
        }
        try {
            return Crypt::decryptString($this->attributes['secondary_secret']);
        } catch (\Throwable $e) {
            return 'Error decrypting secret';
        }
    }

    public function getHasPasswordAttribute(): bool
    {
        return !empty($this->attributes['password']);
    }

    public function getHasSecondarySecretAttribute(): bool
    {
        return !empty($this->attributes['secondary_secret']);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function revealer()
    {
        return $this->belongsTo(User::class, 'last_revealed_by');
    }

    public function accessLogs()
    {
        return $this->hasMany(PasswordAccessLog::class, 'entry_id')->orderBy('created_at', 'desc');
    }
}
