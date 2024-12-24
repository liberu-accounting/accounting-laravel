

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $primaryKey = 'tax_rate_id';

    protected $fillable = [
        'name',
        'rate',
        'description',
        'is_compound',
        'is_active'
    ];

    protected $casts = [
        'rate' => 'float',
        'is_compound' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function calculateTax($amount, $previousTaxes = 0)
    {
        if (!$this->is_active) {
            return 0;
        }

        $taxableAmount = $this->is_compound ? 
            $amount + $previousTaxes : 
            $amount;
            
        return $taxableAmount * ($this->rate / 100);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class);
    }
}