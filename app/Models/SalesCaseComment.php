<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCaseComment extends Model
{
    use HasFactory;

    protected $table = 'sales_case_comments';

    protected $fillable = [
        'case_id',
        'username',
        'comment',
    ];

    public function salesCase()
    {
        return $this->belongsTo(SalesCase::class, 'case_id', 'case_id');
    }
}
