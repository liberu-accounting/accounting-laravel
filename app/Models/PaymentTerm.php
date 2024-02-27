<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    use HasFactory;

    protected $primaryKey = 'payment_term_id';

    protected $fillable = [
        'payment_term_name',
        'payment_term_description',
        'payment_term_number_of_days',
    ];

    public function suppliers()
    {
        return $this->hasMany(Supplier::class,  'payment_term_id');
    }
}
