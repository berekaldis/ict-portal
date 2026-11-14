<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditRequest extends Model
{
    use HasFactory;

    protected $table = 'edit_requests';
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'timestamp',
        'branch',
        'user_name',
        'store_user',
        'voucher_code',
        'voucher_type',
        'action_type',
        'edit_type',
        'reason',
        'target_dept',
        'incorrect_date',
        'correct_date',
        'shift',
        'status',
        'approver',
        'telegram_msg_id',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}
