<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCaseAttachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'sales_case_attachments';

    protected $fillable = [
        'case_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function salesCase()
    {
        return $this->belongsTo(SalesCase::class, 'case_id', 'case_id');
    }
}
