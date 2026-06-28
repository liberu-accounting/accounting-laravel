<?php

declare(strict_types=1);

namespace App\Services;

/**
 * UK statutory payroll tax calculator: PAYE income tax, employee + employer
 * National Insurance, and student-loan repayment. All inputs/outputs are
 * ANNUAL. Figures come from config/payroll.php (2024/25 by default).
 *
 * ponytail: annual basis — pay-period (monthly/weekly) apportionment and
 * cumulative tax-code operation are a follow-up; the band/threshold maths is
 * the part that has to be right.
 */
class PayrollTaxService
{
    public function incomeTax(float $annualGross, string $taxCode = '1257L'): float
    {
        $allowance = $this->allowanceFromCode($taxCode);

        $taper = (float) config('payroll.paye.allowance_taper_threshold');
        if ($annualGross > $taper) {
            $allowance = max(0.0, $allowance - floor(($annualGross - $taper) / 2));
        }

        $taxable = max(0.0, $annualGross - $allowance);

        $tax = 0.0;
        $lower = 0.0;
        foreach (config('payroll.paye.bands') as $band) {
            $upto = $band['upto'] ?? INF;
            $inBand = max(0.0, min($taxable, $upto) - $lower);
            $tax += $inBand * $band['rate'];
            $lower = $upto;
            if ($taxable <= $upto) {
                break;
            }
        }

        return round($tax, 2);
    }

    public function employeeNi(float $annualGross): float
    {
        $c = config('payroll.national_insurance.employee');

        $main = max(0.0, min($annualGross, $c['upper_earnings_limit']) - $c['primary_threshold']) * $c['main_rate'];
        $upper = max(0.0, $annualGross - $c['upper_earnings_limit']) * $c['upper_rate'];

        return round($main + $upper, 2);
    }

    public function employerNi(float $annualGross): float
    {
        $c = config('payroll.national_insurance.employer');

        return round(max(0.0, $annualGross - $c['secondary_threshold']) * $c['rate'], 2);
    }

    public function studentLoan(float $annualGross, ?string $plan): float
    {
        if ($plan === null || $plan === '') {
            return 0.0;
        }

        $c = config('payroll.student_loan.'.$plan);
        if (! $c) {
            return 0.0;
        }

        return round(max(0.0, $annualGross - $c['threshold']) * $c['rate'], 2);
    }

    /**
     * @return array{income_tax: float, employee_ni: float, employer_ni: float, student_loan: float, net: float}
     */
    public function compute(float $annualGross, string $taxCode = '1257L', ?string $studentLoanPlan = null): array
    {
        $incomeTax = $this->incomeTax($annualGross, $taxCode);
        $employeeNi = $this->employeeNi($annualGross);
        $studentLoan = $this->studentLoan($annualGross, $studentLoanPlan);

        return [
            'income_tax' => $incomeTax,
            'employee_ni' => $employeeNi,
            'employer_ni' => $this->employerNi($annualGross),
            'student_loan' => $studentLoan,
            'net' => round($annualGross - $incomeTax - $employeeNi - $studentLoan, 2),
        ];
    }

    /**
     * Personal allowance from a tax code: the numeric part × 10 (e.g. 1257L → 12 570).
     * Falls back to the configured standard allowance for non-numeric codes.
     */
    private function allowanceFromCode(string $taxCode): float
    {
        if (preg_match('/^(\d+)[A-Za-z]?$/', trim($taxCode), $m) === 1) {
            return (float) $m[1] * 10;
        }

        return (float) config('payroll.paye.personal_allowance');
    }
}
