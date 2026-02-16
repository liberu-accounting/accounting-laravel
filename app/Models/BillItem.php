<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'bill_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'tax_amount',
        'tax_rate_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    // Relationships
    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    // Business Logic
    public function calculateAmount()
    {
        $this->amount = $this->quantity * $this->unit_price;
        
        if ($this->taxRate) {
            $this->tax_amount = $this->taxRate->calculateTax($this->amount);
        }
        
        return $this->amount;
    }

    // Auto-calculate amount when saving
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($item) {
            if ($item->isDirty(['quantity', 'unit_price'])) {
                $item->calculateAmount();
            }
        });

        static::saved(function ($item) {
            // Recalculate bill totals
            if ($item->bill) {
                $item->bill->calculateTotals();
            }
        });

        static::deleted(function ($item) {
            // Recalculate bill totals
            if ($item->bill) {
                $item->bill->calculateTotals();
            }
        });
    }
}
