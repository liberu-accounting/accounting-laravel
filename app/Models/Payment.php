<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\IsTenantModel;

class Payment extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $primaryKey = "payment_id";

    #[\Override]
    protected $fillable = [
        "invoice_id",
        "payment_date",
        "payment_amount",
    ];
}
