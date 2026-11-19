<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelecomPhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'account_number',
        'sim_card_number',
        'telecom_provider_id',
        'service_type',
        'package_type',
        'package_start_date',
        'package_expiry_date',
        'monthly_cost',
        'billing_type',
        'assigned_type',
        'user_id',
        'employee_id',
        'branch_id',
        'department_id',
        'status',
        'issue_date',
        'renewal_date',
        'notes',
        'attachment_path',
    ];

    protected $appends = [
        'attachment_url',
    ];

    protected $casts = [
        'monthly_cost' => 'decimal:2',
        'issue_date' => 'date',
        'renewal_date' => 'date',
        'package_start_date' => 'date',
        'package_expiry_date' => 'date',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->attachment_path) {
            return asset('storage/' . $this->attachment_path);
        }
        return null;
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TelecomProvider::class, 'telecom_provider_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
