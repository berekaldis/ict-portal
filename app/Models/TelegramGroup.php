<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramGroup extends Model
{
    use HasFactory;

    protected $table = 'telegram_groups';

    protected $fillable = [
        'name',
        'chat_id',
        'username',
        'group_type',
        'category',
        'purpose',
        'description',
        'branch_id',
        'department_id',
        'managed_by',
        'created_by',
        'status',
        'invite_link',
        'qr_code_path',
        'is_public',
        'member_count',
        'link_generated_at',
        'notes',
        'ticket_id',
        'title',
        'telegram_chat_id',
    ];

    protected $casts = [
        'is_public'         => 'boolean',
        'member_count'      => 'integer',
        'link_generated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($group) {
            if (!empty($group->name) && empty($group->title)) {
                $group->title = $group->name;
            }
            if (!empty($group->chat_id) && is_numeric($group->chat_id)) {
                $group->telegram_chat_id = (int) $group->chat_id;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get a clean Telegram public link (https://t.me/username or https://t.me/+hash)
     */
    public function getTelegramUrlAttribute(): ?string
    {
        if ($this->username) {
            return 'https://t.me/' . ltrim($this->username, '@');
        }
        if ($this->invite_link) {
            return $this->invite_link;
        }
        return null;
    }

    /**
     * Scope: Active groups only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}
