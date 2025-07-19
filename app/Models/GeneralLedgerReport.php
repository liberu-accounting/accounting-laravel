<?php

namespace App\Models;

use PDF;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedgerReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'report_type',
        'data',
        'template_name',
        'is_template',
        'chart_type',
        'filters',
        'custom_fields'
    ];

    protected $casts = [
        'report_date' => 'date',
        'data' => 'array',
        'filters' => 'array',
        'is_template' => 'boolean',
        'custom_fields' => 'array'
    ];

    public const REPORT_TYPES = [
        'balance_sheet' => 'Balance Sheet',
        'income_statement' => 'Income Statement',
        'cash_flow' => 'Cash Flow Statement',
        'custom' => 'Custom Report'
    ];

    public const CHART_TYPES = [
        'bar' => 'Bar Chart',
        'line' => 'Line Chart',
        'pie' => 'Pie Chart',
        'none' => 'No Chart'
    ];

    public function generatePdf()
    {
        // Implementation for PDF generation
        $pdf = PDF::loadView('reports.general-ledger', [
            'report' => $this
        ]);
        
        return $pdf->download($this->template_name . '.pdf');
    }
}