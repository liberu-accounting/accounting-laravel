<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model
{
    use HasFactory;

    protected $primaryKey = "invoice_id";

   protected $fillable = [
    "customer_id",
    "vendor_id",
    "invoice_number",
    "invoice_date",
    "due_date",
    "total_amount",
    "tax_amount",
    "tax_rate_id",
    "payment_status",
    "is_recurring",
    "recurrence_frequency",
    "recurrence_start",
    "recurrence_end",
    "last_generated",
    "approval_status",
    "rejection_reason",
    "approved_by",
    "approved_at",
    "document_path",
    "notes",
];

protected $casts = [
    'total_amount' => 'decimal:2',
    'tax_amount' => 'decimal:2',
    'invoice_date' => 'date',
    'due_date' => 'date',
    'is_recurring' => 'boolean',
    'recurrence_start' => 'date',
    'recurrence_end' => 'date',
    'last_generated' => 'date',
    'approved_at' => 'datetime',
];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'invoice_id');
    }

    public function creditMemos()
    {
        return $this->hasMany(CreditMemo::class, 'invoice_id');
    }

    public function calculateTax()
    {
        if (!$this->taxRate) {
            return 0;
        }

        $baseAmount = $this->total_amount;
        $previousTaxes = 0;
        
        if ($this->taxRate->is_compound) {
            // Get all non-compound taxes first
            $nonCompoundTaxes = TaxRate::where('is_active', true)
                ->where('is_compound', false)
                ->get();
                
            foreach ($nonCompoundTaxes as $tax) {
                $previousTaxes += $tax->calculateTax($baseAmount);
            }
        }

        $taxAmount = $this->taxRate->calculateTax($baseAmount, $previousTaxes);
        $this->tax_amount = $taxAmount;
        return $taxAmount;
    }

    public function getTotalWithTax()
    {
        return $this->total_amount + $this->tax_amount;
    }

    public function calculateTotalFromTimeEntries()
    {
        $this->total_amount = $this->timeEntries->sum('total_amount');
        return $this->total_amount;
    }

    public function generatePDF()
    {
        $data = [
            'invoice' => $this,
            'customer' => $this->customer,
            'vendor' => $this->vendor,
            'tax_rate' => $this->taxRate,
        ];
        
        $pdf = PDF::loadView('invoices.template', $data);
        return $pdf->download('invoice_' . $this->invoice_number . '.pdf');
    }

    public function generateRecurring()
    {
        if (!$this->is_recurring || !$this->shouldGenerateNew()) {
            return;
        }

        $newInvoice = $this->replicate();
        $newInvoice->invoice_date = $this->getNextDate();
        $newInvoice->due_date = $this->getNextDate()->addDays(30);
        $newInvoice->payment_status = 'pending';
        $newInvoice->save();

        $this->last_generated = now();
        $this->save();
    }

    private function shouldGenerateNew(): bool 
    {
        if ($this->recurrence_end && $this->recurrence_end < now()) {
            return false;
        }

        $lastDate = $this->last_generated ?? $this->recurrence_start;
        return $this->getNextDate()->lte(now());
    }

    private function getNextDate(): Carbon
    {
        $lastDate = $this->last_generated ?? $this->recurrence_start;
        
        return match($this->recurrence_frequency) {
            'daily' => $lastDate->addDay(),
            'weekly' => $lastDate->addWeek(),
            'monthly' => $lastDate->addMonth(),
            'yearly' => $lastDate->addYear(),
            default => $lastDate
        };

    }
    public function approve()
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        event(new InvoiceApproved($this));
    }

    public function reject($reason)
    {
        $this->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        event(new InvoiceRejected($this));
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-' . str_pad(static::max('invoice_id') + 1, 6, '0', STR_PAD_LEFT);
            }
            if (empty($invoice->approval_status)) {
                $invoice->approval_status = 'pending';
            }
        });
    }
}
