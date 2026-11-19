<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelecomBroadband extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'service_number',
        'connection_name',
        'connection_type',
        'telecom_provider_id',
        'package_type',
        'package_start_date',
        'package_expiry_date',
        'bandwidth_speed',
        'monthly_cost',
        'billing_type',
        'branch_id',
        'department_id',
        'installation_address',
        'ip_address',
        'equipment_details',
        'contract_start_date',
        'contract_expiry_date',
        'status',
        'notes',
        'attachment_path',
    ];

    protected $appends = [
        'attachment_url',
    ];

    protected $casts = [
        'monthly_cost' => 'decimal:2',
        'contract_start_date' => 'date',
        'contract_expiry_date' => 'date',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
