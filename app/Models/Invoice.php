<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $primaryKey = "invoice_id";

    protected $fillable = [
        "customer_id",
        "invoice_date",
        "total_amount",
        "payment_status"
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
