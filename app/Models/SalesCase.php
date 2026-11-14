<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCase extends Model
{
    use HasFactory;

    protected $table = 'sales_cases';

    protected $fillable = [
        'case_id',
        'date',
        'branch_id',
        'branch_name',
        'z_report',
        'cash_sales',
        'difference',
        'progress',
        'remark',
        'submitted_by',
        'technical_name',
        'escalated_to',
        'escalation_date',
        'case_fixed_date',
        'approved',
        'approved_by',
        'approved_at',
        'attachment_file',
        'attachment_name',
        'attachment_type',
        'attachments',
    ];

    protected $casts = [
        'date' => 'date',
        'z_report' => 'float',
        'cash_sales' => 'float',
        'difference' => 'float',
        'approved' => 'boolean',
        'escalation_date' => 'datetime',
        'case_fixed_date' => 'datetime',
        'approved_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function comments()
    {
        return $this->hasMany(SalesCaseComment::class, 'case_id', 'case_id')->orderBy('created_at', 'asc');
    }

    public function history()
    {
        return $this->hasMany(SalesCaseHistory::class, 'case_id', 'case_id')->orderBy('created_at', 'desc');
    }

    public function caseAttachments()
    {
        return $this->hasMany(SalesCaseAttachment::class, 'case_id', 'case_id')->orderBy('uploaded_at', 'desc');
    }
}
