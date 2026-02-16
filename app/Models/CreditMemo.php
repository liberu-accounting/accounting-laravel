<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditMemo extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'credit_memo_id';

    protected $fillable = [
        'customer_id',
        'invoice_id',
        'credit_memo_number',
        'credit_memo_date',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'amount_applied',
        'tax_rate_id',
        'status',
        'reason',
        'notes',
        'document_path',
    ];

    protected $casts = [
        'credit_memo_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_applied' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function items()
    {
        return $this->hasMany(CreditMemoItem::class, 'credit_memo_id', 'credit_memo_id');
    }

    public function applications()
    {
        return $this->hasMany(CreditMemoApplication::class, 'credit_memo_id', 'credit_memo_id');
    }

    // Calculated Attributes
    public function getAmountRemainingAttribute()
    {
        return $this->total_amount - $this->amount_applied;
    }

    public function getIsFullyAppliedAttribute()
    {
        return $this->amount_applied >= $this->total_amount;
    }

    // Business Logic Methods
    public function calculateTax()
    {
        if (!$this->taxRate) {
            return 0;
        }

        $baseAmount = $this->subtotal_amount;
        $previousTaxes = 0;
        
        if ($this->taxRate->is_compound) {
            $nonCompoundTaxes = TaxRate::where('is_active', true)
                ->where('is_compound', false)
                ->get();
                
            foreach ($nonCompoundTaxes as $tax) {
                $previousTaxes += $tax->calculateTax($baseAmount);
            }
        }

        $taxAmount = $this->taxRate->calculateTax($baseAmount, $previousTaxes);
        $this->tax_amount = $taxAmount;
        $this->total_amount = $this->subtotal_amount + $taxAmount;
        
        return $taxAmount;
    }

    public function calculateTotals()
    {
        $this->subtotal_amount = $this->items->sum('amount');
        $this->calculateTax();
        $this->save();
    }

    public function applyToInvoice($invoiceId, $amount, $applicationDate = null)
    {
        if ($this->amount_remaining < $amount) {
            throw new \Exception('Cannot apply more than the remaining credit memo amount.');
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            throw new \Exception('Invoice not found.');
        }

        if ($invoice->customer_id !== $this->customer_id) {
            throw new \Exception('Credit memo and invoice must belong to the same customer.');
        }

        // Create application record
        $application = $this->applications()->create([
            'invoice_id' => $invoiceId,
            'amount_applied' => $amount,
            'application_date' => $applicationDate ?? now(),
        ]);

        // Update amount applied
        $this->amount_applied = $this->applications()->sum('amount_applied');
        
        // Update status
        if ($this->amount_applied >= $this->total_amount) {
            $this->status = 'applied';
        } else {
            $this->status = 'open';
        }
        
        $this->save();

        return $application;
    }

    public function markAsVoid()
    {
        $this->update([
            'status' => 'void',
        ]);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->whereRaw('amount_applied < total_amount');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    // Auto-generate credit memo number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($creditMemo) {
            if (empty($creditMemo->credit_memo_number)) {
                $creditMemo->credit_memo_number = static::generateCreditMemoNumber();
            }
            if (empty($creditMemo->status)) {
                $creditMemo->status = 'draft';
            }
        });
    }

    public static function generateCreditMemoNumber()
    {
        $prefix = 'CM';
        $year = date('Y');
        $lastCreditMemo = static::whereYear('created_at', $year)
            ->orderBy('credit_memo_number', 'desc')
            ->first();

        if (!$lastCreditMemo) {
            $number = 1;
        } else {
            $parts = explode('-', $lastCreditMemo->credit_memo_number);
            $number = isset($parts[1]) ? ((int)$parts[1]) + 1 : 1;
        }

        return $prefix . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
