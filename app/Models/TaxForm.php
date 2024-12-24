

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Barryvdh\DomPDF\Facade\Pdf;

class TaxForm extends Model
{
    use HasFactory;

    protected $primaryKey = 'tax_form_id';

    protected $fillable = [
        'form_type',
        'customer_id',
        'tax_year',
        'total_payments',
        'total_tax_withheld',
        'status',
        'form_data'
    ];

    protected $casts = [
        'form_data' => 'array',
        'total_payments' => 'decimal:2',
        'total_tax_withheld' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function generatePDF()
    {
        $data = [
            'form' => $this,
            'customer' => $this->customer,
        ];
        
        $pdf = PDF::loadView('tax-forms.' . strtolower($this->form_type), $data);
        return $pdf->download($this->form_type . '_' . $this->tax_year . '.pdf');
    }

    public function calculateTotals()
    {
        $invoices = Invoice::where('customer_id', $this->customer_id)
            ->whereYear('invoice_date', $this->tax_year)
            ->get();

        $this->total_payments = $invoices->sum('total_amount');
        $this->total_tax_withheld = $invoices->sum('tax_amount');
        $this->save();
    }
}