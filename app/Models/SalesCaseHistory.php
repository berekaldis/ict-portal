<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCaseHistory extends Model
{
    use HasFactory;

    protected $table = 'sales_case_history';

    protected $fillable = [
        'case_id',
        'username',
        'action',
        'details',
    ];

    public function salesCase()
    {
        return $this->belongsTo(SalesCase::class, 'case_id', 'case_id');
    }
}
