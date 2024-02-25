<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $primaryKey = 'supplier_id';

    protected $fillable = [
        'payment_term_id',
        'supplier_first_name',
        'supplier_last_name',
        'supplier_email',
        'supplier_address',
        'supplier_phone_number',
        'supplier_limit_credit',
        'supplier_tin'
    ];

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class,  'payment_term_id');
    }


}
