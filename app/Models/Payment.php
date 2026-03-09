<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\IsTenantModel;

class Payment extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $primaryKey = "payment_id";

    protected $fillable = [
        "invoice_id",
        "payment_date",
        "payment_amount",
    ];
}
