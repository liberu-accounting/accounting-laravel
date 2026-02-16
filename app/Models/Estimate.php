<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimate extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'estimate_id';

    protected $fillable = [
        'customer_id',
        'estimate_number',
        'estimate_date',
        'expiration_date',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'tax_rate_id',
        'status',
        'invoice_id',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'declined_at',
        'decline_reason',
        'notes',
        'terms',
        'document_path',
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'expiration_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function items()
    {
        return $this->hasMany(EstimateItem::class, 'estimate_id', 'estimate_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Calculated Attributes
    public function getIsExpiredAttribute()
    {
        if (!$this->expiration_date || $this->status === 'accepted') {
            return false;
        }
        
        return Carbon::now()->isAfter($this->expiration_date);
    }

    public function getDaysUntilExpirationAttribute()
    {
        if (!$this->expiration_date) {
            return null;
        }
        
        return Carbon::now()->diffInDays($this->expiration_date, false);
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

    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsViewed()
    {
        if (!$this->viewed_at) {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function decline($reason = null)
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $reason,
        ]);
    }

    public function convertToInvoice()
    {
        if ($this->status !== 'accepted') {
            throw new \Exception('Only accepted estimates can be converted to invoices.');
        }

        if ($this->invoice_id) {
            throw new \Exception('This estimate has already been converted to an invoice.');
        }

        // Create invoice
        $invoice = Invoice::create([
            'customer_id' => $this->customer_id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'total_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'tax_rate_id' => $this->tax_rate_id,
            'payment_status' => 'pending',
            'notes' => "Converted from Estimate {$this->estimate_number}",
        ]);

        // Copy items (Note: Invoice uses TimeEntry, not invoice items)
        // This would need to be adjusted based on actual invoice structure
        // For now, just link the estimate to the invoice
        
        $this->update([
            'invoice_id' => $invoice->invoice_id,
        ]);

        return $invoice;
    }

    // Scopes
    public function scopeExpired($query)
    {
        return $query->where('expiration_date', '<', now())
            ->whereNotIn('status', ['accepted', 'declined']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'viewed'])
            ->where(function($q) {
                $q->whereNull('expiration_date')
                  ->orWhere('expiration_date', '>=', now());
            });
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Auto-generate estimate number and check expiration
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($estimate) {
            if (empty($estimate->estimate_number)) {
                $estimate->estimate_number = static::generateEstimateNumber();
            }
            if (empty($estimate->status)) {
                $estimate->status = 'draft';
            }
        });

        static::saving(function ($estimate) {
            // Auto-expire if past expiration date
            if ($estimate->expiration_date && 
                Carbon::now()->isAfter($estimate->expiration_date) &&
                !in_array($estimate->status, ['accepted', 'declined', 'expired'])) {
                $estimate->status = 'expired';
            }
        });
    }

    public static function generateEstimateNumber()
    {
        $prefix = 'EST';
        $year = date('Y');
        $lastEstimate = static::whereYear('created_at', $year)
            ->orderBy('estimate_number', 'desc')
            ->first();

        if (!$lastEstimate) {
            $number = 1;
        } else {
            $parts = explode('-', $lastEstimate->estimate_number);
            $number = isset($parts[1]) ? ((int)$parts[1]) + 1 : 1;
        }

        return $prefix . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
