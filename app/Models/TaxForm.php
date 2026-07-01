<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxForm extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $primaryKey = 'tax_form_id';

    #[\Override]
    protected $fillable = [
        'form_type',
        'customer_id',
        'tax_year',
        'total_payments',
        'total_tax_withheld',
        'status',
        'form_data',
    ];

    #[\Override]
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

        $pdf = Pdf::loadView('tax-forms.'.strtolower($this->form_type), $data);

        return $pdf->download($this->form_type.'_'.$this->tax_year.'.pdf');
    }

    public function getTaxSummaryAttribute()
    {
        return $this->form_data['tax_summary'] ?? [];
    }
}
