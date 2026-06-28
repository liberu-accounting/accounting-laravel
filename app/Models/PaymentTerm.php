<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $primaryKey = 'payment_term_id';

    #[\Override]
    protected $fillable = [
        'payment_term_name',
        'payment_term_description',
        'payment_term_number_of_days',
    ];

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'payment_term_id');
    }
}
