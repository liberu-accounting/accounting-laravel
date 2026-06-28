<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayslipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipServiceTest extends TestCase
{
    use RefreshDatabase;

    private function payroll(): Payroll
    {
        $employee = Employee::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'position' => 'Engineer',
            'hire_date' => '2024-01-01',
            'tax_id' => 'TX-'.uniqid(),
        ]);

        $payroll = new Payroll([
            'employee_id' => $employee->id,
            'base_salary' => 30000,   // annual gross drives the PAYE/NI engine
            'overtime_hours' => 0,
            'overtime_rate' => 0,
            'other_deductions' => 0,
            'pay_period_start' => '2026-06-01',
            'pay_period_end' => '2026-06-30',
            'payment_date' => '2026-06-30',
            'payment_status' => 'paid',
        ]);
        // PAYE 3,486.00 + employee NI 1,394.40 = 4,880.40; net = 25,119.60
        $payroll->calculateNetSalary();
        $payroll->save();

        return $payroll;
    }

    public function test_gross_and_deduction_helpers(): void
    {
        $payroll = $this->payroll();

        $this->assertEquals(30000.00, $payroll->grossSalary());
        $this->assertEquals(4880.40, $payroll->totalDeductions()); // PAYE 3,486 + EE NI 1,394.40
        $this->assertEquals(25119.60, $payroll->net_salary);
    }

    public function test_payslip_html_shows_employee_gross_deductions_net(): void
    {
        $payroll = $this->payroll();

        $html = app(PayslipService::class)->html($payroll);

        $this->assertStringContainsString('Jane Doe', $html);
        $this->assertStringContainsString('30,000.00', $html);  // gross
        $this->assertStringContainsString('4,880.40', $html);   // total deductions
        $this->assertStringContainsString('25,119.60', $html);  // net
    }

    public function test_payslip_pdf_returns_bytes(): void
    {
        $payroll = $this->payroll();

        $pdf = app(PayslipService::class)->pdf($payroll);

        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
