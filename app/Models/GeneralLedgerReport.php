<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedgerReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'report_type',
        'data',
    ];

    protected $casts = [
        'report_date' => 'date',
        'data' => 'array',
    ];
}