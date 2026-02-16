<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcPayeSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'hmrc_submission_id',
        'tax_year',
        'tax_month',
        'payment_date',
        'fps_type',
        'employee_count',
        'total_gross_pay',
        'total_paye_tax',
        'total_employee_ni',
        'total_employer_ni',
        'total_student_loan',
        'employee_data',
        'late_reason_provided',
        'late_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_gross_pay' => 'decimal:2',
        'total_paye_tax' => 'decimal:2',
        'total_employee_ni' => 'decimal:2',
        'total_employer_ni' => 'decimal:2',
        'total_student_loan' => 'decimal:2',
        'employee_data' => 'array',
        'late_reason_provided' => 'boolean',
    ];

    /**
     * Get the company that owns the PAYE submission.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the HMRC submission for this PAYE submission.
     */
    public function hmrcSubmission(): BelongsTo
    {
        return $this->belongsTo(HmrcSubmission::class);
    }

    /**
     * Calculate PAYE submission from payroll data.
     */
    public function calculateFromPayroll(): void
    {
        $payrollRecords = Payroll::where('company_id', $this->company_id)
            ->whereYear('pay_period_end', '=', $this->getTaxYearEnd())
            ->whereMonth('pay_period_end', '=', $this->tax_month)
            ->get();

        $employeeData = [];
        $totalGross = 0;
        $totalPaye = 0;
        $totalEmployeeNi = 0;
        $totalEmployerNi = 0;
        $totalStudentLoan = 0;

        foreach ($payrollRecords as $payroll) {
            $employee = $payroll->employee;
            
            $employeeData[] = [
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'nino' => $employee->national_insurance_number,
                'gross_pay' => $payroll->gross_pay,
                'paye_tax' => $payroll->income_tax,
                'employee_ni' => $payroll->employee_ni_contribution,
                'employer_ni' => $payroll->employer_ni_contribution,
                'student_loan' => $payroll->student_loan_deduction ?? 0,
            ];

            $totalGross += $payroll->gross_pay;
            $totalPaye += $payroll->income_tax;
            $totalEmployeeNi += $payroll->employee_ni_contribution;
            $totalEmployerNi += $payroll->employer_ni_contribution;
            $totalStudentLoan += $payroll->student_loan_deduction ?? 0;
        }

        $this->employee_count = count($employeeData);
        $this->employee_data = $employeeData;
        $this->total_gross_pay = $totalGross;
        $this->total_paye_tax = $totalPaye;
        $this->total_employee_ni = $totalEmployeeNi;
        $this->total_employer_ni = $totalEmployerNi;
        $this->total_student_loan = $totalStudentLoan;
        
        $this->save();
    }

    /**
     * Get tax year end year from tax_year string.
     */
    private function getTaxYearEnd(): int
    {
        // Tax year format: "2023-24" -> returns 2024
        return (int) substr($this->tax_year, -2) + 2000;
    }

    /**
     * Get the submission status.
     */
    public function getStatusAttribute(): string
    {
        if ($this->hmrcSubmission) {
            return $this->hmrcSubmission->status;
        }
        
        return 'draft';
    }

    /**
     * Check if submission can be edited.
     */
    public function isEditable(): bool
    {
        return !$this->hmrcSubmission || $this->hmrcSubmission->isEditable();
    }

    /**
     * Check if submission is late.
     */
    public function isLate(): bool
    {
        // RTI submissions should be on or before payment date
        return $this->created_at->isAfter($this->payment_date);
    }
}
