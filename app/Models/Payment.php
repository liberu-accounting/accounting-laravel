<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $primaryKey = "payment_id";

    protected $fillable = [
        "invoice_id",
        "payment_date",
        "payment_amount",
    ];
}
