<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ExchangeRateService;

class Invoice extends Model
{
    use HasFactory;

    protected $primaryKey = "invoice_id";

    protected $fillable = [
        "customer_id",
        "invoice_number",
        "invoice_date",
        "due_date",
        "total_amount",
        "tax_amount",
        "tax_rate_id",
        "payment_status",
        "notes",
        "currency_id"
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'invoice_id');
    }

    public function getAmountInDefaultCurrency()
    {
        if (!$this->currency_id) {
            return $this->total_amount;
        }

        $defaultCurrency = Currency::where('is_default', true)->first();
        if ($this->currency_id === $defaultCurrency->currency_id) {
            return $this->total_amount;
        }

        $exchangeRateService = app(ExchangeRateService::class);
        $rate = $exchangeRateService->getExchangeRate(
            $this->currency,
            $defaultCurrency
        );
        
        return $this->total_amount * $rate;
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
            'tax_rate' => $this->taxRate,
            'currency' => $this->currency,
        ];
        
        $pdf = PDF::loadView('invoices.template', $data);
        return $pdf->download('invoice_' . $this->invoice_number . '.pdf');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::observe(InvoiceObserver::class);
        
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-' . str_pad(static::max('invoice_id') + 1, 6, '0', STR_PAD_LEFT);
            }
            
            if (empty($invoice->currency_id)) {
                $invoice->currency_id = Currency::where('is_default', true)->first()->currency_id;
            }
        });
    }
}
