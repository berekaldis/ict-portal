<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItHardwareAsset extends Model
{
    use HasFactory;

    protected $table = 'it_hardware_assets';

    protected $fillable = [
        'branch_id',
        'branch_name',
        'asset_tag',
        'serial_number',
        'asset_type',
        'brand',
        'model',
        'specifications',
        'purchase_date',
        'warranty_expiry',
        'status',
        'assigned_to',
        'location',
        'ip_address',
        'notes',
        'photo_path',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
    ];
}
