<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditMemoItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'credit_memo_id',
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
    public function creditMemo()
    {
        return $this->belongsTo(CreditMemo::class, 'credit_memo_id', 'credit_memo_id');
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
            // Recalculate credit memo totals
            if ($item->creditMemo) {
                $item->creditMemo->calculateTotals();
            }
        });

        static::deleted(function ($item) {
            // Recalculate credit memo totals
            if ($item->creditMemo) {
                $item->creditMemo->calculateTotals();
            }
        });
    }
}
