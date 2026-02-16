<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'bill_id';

    protected $fillable = [
        'vendor_id',
        'bill_number',
        'bill_date',
        'due_date',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'tax_rate_id',
        'status',
        'payment_status',
        'purchase_order_id',
        'reference_number',
        'notes',
        'document_path',
        'approved_by',
        'approved_at',
        'approval_status',
        'rejection_reason',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'purchase_order_id');
    }

    public function items()
    {
        return $this->hasMany(BillItem::class, 'bill_id', 'bill_id');
    }

    public function payments()
    {
        return $this->hasMany(BillPayment::class, 'bill_id', 'bill_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Calculated Attributes
    public function getAmountDueAttribute()
    {
        return $this->total_amount - $this->amount_paid;
    }

    public function getDaysOverdueAttribute()
    {
        if ($this->payment_status === 'paid' || !$this->due_date) {
            return 0;
        }
        
        $daysOverdue = Carbon::now()->diffInDays($this->due_date, false);
        return $daysOverdue < 0 ? abs($daysOverdue) : 0;
    }

    public function getIsOverdueAttribute()
    {
        return $this->days_overdue > 0;
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

    public function recordPayment(array $paymentData)
    {
        $payment = $this->payments()->create($paymentData);
        
        // Update amount paid
        $this->amount_paid = $this->payments()->sum('amount');
        
        // Update payment status
        if ($this->amount_paid >= $this->total_amount) {
            $this->payment_status = 'paid';
            $this->status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->payment_status = 'partial';
        }
        
        $this->save();
        
        return $payment;
    }

    public function approve()
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'status' => 'open',
        ]);
    }

    public function reject($reason)
    {
        $this->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    public function markAsVoid()
    {
        $this->update([
            'status' => 'void',
        ]);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('payment_status', '!=', 'paid')
            ->where('due_date', '<', now());
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', '!=', 'paid');
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Auto-generate bill number on creation
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($bill) {
            if (empty($bill->bill_number)) {
                $bill->bill_number = static::generateBillNumber();
            }
            if (empty($bill->approval_status)) {
                $bill->approval_status = 'pending';
            }
            if (empty($bill->status)) {
                $bill->status = 'draft';
            }
            if (empty($bill->payment_status)) {
                $bill->payment_status = 'unpaid';
            }
        });

        // Update status when due date passes
        static::saving(function ($bill) {
            if ($bill->isDirty('due_date') || $bill->isDirty('payment_status')) {
                if ($bill->payment_status !== 'paid' && $bill->due_date < now()) {
                    $bill->status = 'overdue';
                }
            }
        });
    }

    public static function generateBillNumber()
    {
        $prefix = 'BILL';
        $year = date('Y');
        $lastBill = static::whereYear('created_at', $year)
            ->orderBy('bill_number', 'desc')
            ->first();

        if (!$lastBill) {
            $number = 1;
        } else {
            // Extract number from bill_number (e.g., BILL2026-0001)
            $parts = explode('-', $lastBill->bill_number);
            $number = isset($parts[1]) ? ((int)$parts[1]) + 1 : 1;
        }

        return $prefix . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
