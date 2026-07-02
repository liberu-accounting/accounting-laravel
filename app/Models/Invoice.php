<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model
{
    use HasFactory;
    use IsTenantModel;

    // protected $primaryKey = "invoice_id";

    #[\Override]
    protected $fillable = [
        'customer_id',
        'vendor_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'total_amount',
        'payment_status',
        'is_recurring',
        'recurrence_frequency',
        'recurrence_start',
        'recurrence_end',
        'last_generated',
        'approval_status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'document_path',
        'notes',
        'team_id',
    ];

    #[\Override]
    protected $casts = [
        'total_amount' => 'decimal:2',
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

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'invoice_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    /**
     * Roll the line-item amounts up into the invoice total.
     */
    public function calculateTotals(): void
    {
        $this->total_amount = (float) $this->items()->sum('amount');
        $this->save();
    }

    /**
     * Compute and persist this invoice's late fee, returning the amount charged.
     *
     * // ponytail: flat percentage of total_amount, charged once the due date plus
     * // grace period has fully passed; recompute overwrites (idempotent — the command
     * // reruns, so accumulating would double-charge). No daily accrual / compounding —
     * // add that only if a real late-fee spec ever calls for it.
     */
    public function calculateLateFee(): float
    {
        if ($this->payment_status === 'paid' || $this->due_date === null) {
            return 0.0;
        }

        $overdueAfter = $this->due_date->copy()->addDays((int) $this->grace_period_days);

        if (today()->lte($overdueAfter)) {
            return 0.0;
        }

        $fee = round((float) $this->total_amount * (float) $this->late_fee_percentage / 100, 2);

        $this->late_fee_amount = $fee;
        $this->save();

        return $fee;
    }

    public function creditMemos()
    {
        return $this->hasMany(CreditMemo::class, 'invoice_id');
    }

    // ponytail: invoice-level tax removed — the live `invoices` table has no
    // tax_amount / tax_rate_id columns, so the old calculateTax() was silently
    // inert (it early-returned on the always-null taxRate relation). Tax lives in
    // exactly ONE place now: per-line on invoice_items (InvoiceItem::calculateAmount
    // + its tax_amount column). getTotalWithTax() sums that line-item tax.
    public function getTotalWithTax(): float
    {
        return (float) $this->total_amount + (float) $this->items()->sum('tax_amount');
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
        ];

        $pdf = Pdf::loadView('invoices.template', $data);

        return $pdf->download('invoice_'.$this->invoice_number.'.pdf');
    }

    public function generateRecurring(): void
    {
        if (! $this->is_recurring || ! $this->shouldGenerateNew()) {
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

        return $this->getNextDate()->lte(now());
    }

    private function getNextDate(): Carbon
    {
        $lastDate = $this->last_generated ?? $this->recurrence_start;

        return match ($this->recurrence_frequency) {
            'daily' => $lastDate->addDay(),
            'weekly' => $lastDate->addWeek(),
            'monthly' => $lastDate->addMonth(),
            'yearly' => $lastDate->addYear(),
            default => $lastDate
        };

    }

    public function approve(): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        event(new InvoiceApproved($this));
    }

    public function reject($reason): void
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

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice): void {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-'.str_pad((string) ((int) static::max('id') + 1), 6, '0', STR_PAD_LEFT);
            }
            if (empty($invoice->approval_status)) {
                $invoice->approval_status = 'pending';
            }
        });
    }
}
