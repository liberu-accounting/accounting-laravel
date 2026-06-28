<?php

declare(strict_types=1);

namespace App\Services;

/**
 * UK statutory payroll tax calculator: PAYE income tax, employee + employer
 * National Insurance, and student-loan repayment. Annual methods are the core;
 * computeForPeriod() apportions to a pay period. Figures come from
 * config/payroll.php, keyed by tax year (default 2024/25); forYear() selects one.
 *
 * ponytail: period apportionment is even-pay / non-cumulative (W1/M1) basis.
 * True cumulative (year-to-date recalculation from pay history) needs a payslip
 * ledger — a follow-up; isCumulative() already classifies the code.
 */
class PayrollTaxService
{
    /** Periods per year for each pay frequency. */
    public const PERIODS = ['weekly' => 52, 'fortnightly' => 26, 'monthly' => 12];

    private string $taxYear;

    public function __construct(?string $taxYear = null)
    {
        $this->taxYear = $taxYear ?? (string) config('payroll.default_tax_year');
    }

    /**
     * A calculator bound to a specific tax year (e.g. '2025-26').
     */
    public function forYear(string $taxYear): self
    {
        return new self($taxYear);
    }

    /**
     * Read a value from the selected tax year's tables.
     */
    private function cfg(string $path): mixed
    {
        return config("payroll.tax_years.{$this->taxYear}.{$path}");
    }

    public function incomeTax(float $annualGross, string $taxCode = '1257L'): float
    {
        $allowance = $this->allowanceFromCode($taxCode);

        $taper = (float) $this->cfg('paye.allowance_taper_threshold');
        if ($annualGross > $taper) {
            $allowance = max(0.0, $allowance - floor(($annualGross - $taper) / 2));
        }

        $taxable = max(0.0, $annualGross - $allowance);

        $tax = 0.0;
        $lower = 0.0;
        foreach ($this->cfg('paye.bands') as $band) {
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
        $c = $this->cfg('national_insurance.employee');

        $main = max(0.0, min($annualGross, $c['upper_earnings_limit']) - $c['primary_threshold']) * $c['main_rate'];
        $upper = max(0.0, $annualGross - $c['upper_earnings_limit']) * $c['upper_rate'];

        return round($main + $upper, 2);
    }

    public function employerNi(float $annualGross): float
    {
        $c = $this->cfg('national_insurance.employer');

        return round(max(0.0, $annualGross - $c['secondary_threshold']) * $c['rate'], 2);
    }

    public function studentLoan(float $annualGross, ?string $plan): float
    {
        if ($plan === null || $plan === '') {
            return 0.0;
        }

        $c = $this->cfg('student_loan.'.$plan);
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
     * Statutory figures for one pay period (even-pay / non-cumulative basis):
     * annualise the period gross, compute, then divide each figure by the
     * number of periods. This is exactly how a W1/M1 (non-cumulative) code runs.
     *
     * @param  int|string  $periods  periods-per-year, or a frequency key ('monthly', 'weekly', 'fortnightly')
     * @return array{income_tax: float, employee_ni: float, employer_ni: float, student_loan: float, net: float}
     */
    public function computeForPeriod(float $periodGross, int|string $periods, string $taxCode = '1257L', ?string $studentLoanPlan = null): array
    {
        $periodsPerYear = is_string($periods) ? (self::PERIODS[$periods] ?? 12) : $periods;

        $annual = $this->compute($periodGross * $periodsPerYear, $taxCode, $studentLoanPlan);

        return array_map(fn (float $v): float => round($v / $periodsPerYear, 2), $annual);
    }

    /**
     * Whether a tax code operates cumulatively. A W1/M1/X suffix means
     * non-cumulative (each period stands alone); anything else is cumulative.
     */
    public function isCumulative(string $taxCode): bool
    {
        return preg_match('/\b(W1|M1|X)$/i', trim($taxCode)) !== 1;
    }

    /**
     * Personal allowance from a tax code: the leading numeric part × 10
     * (e.g. 1257L → 12 570, and "1257L W1" → 12 570). Falls back to the
     * configured standard allowance for codes with no numeric part (BR/D0/NT).
     */
    private function allowanceFromCode(string $taxCode): float
    {
        if (preg_match('/^(\d+)/', trim($taxCode), $m) === 1) {
            return (float) $m[1] * 10;
        }

        return (float) $this->cfg('paye.personal_allowance');
    }
}
