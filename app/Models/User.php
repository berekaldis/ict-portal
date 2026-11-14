<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'full_name',
        'email',
        'password',
        'role',
        'is_active',
        'must_change_password',
        'department',
        'branch',
        'phone_number',
        'chat_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    protected $appends = ['first_name', 'last_name', 'employee_code'];

    public function getFirstNameAttribute(): string
    {
        $parts = explode(' ', trim($this->name ?: $this->username ?: ''));
        return $parts[0] ?? '';
    }

    public function getLastNameAttribute(): string
    {
        $parts = explode(' ', trim($this->name ?: $this->username ?: ''));
        return isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
    }

    public function getEmployeeCodeAttribute(): string
    {
        return $this->username ?: 'EMP-' . $this->id;
    }
}
