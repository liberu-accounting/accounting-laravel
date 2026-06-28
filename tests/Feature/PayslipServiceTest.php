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
            'base_salary' => 3000,
            'overtime_hours' => 10,
            'overtime_rate' => 20, // overtime pay = 200, gross = 3200
            'other_deductions' => 100,
            'pay_period_start' => '2026-06-01',
            'pay_period_end' => '2026-06-30',
            'payment_date' => '2026-06-30',
            'payment_status' => 'paid',
        ]);
        $payroll->calculateNetSalary(); // tax = 640, net = 3200 - 640 - 100 = 2460
        $payroll->save();

        return $payroll;
    }

    public function test_gross_and_deduction_helpers(): void
    {
        $payroll = $this->payroll();

        $this->assertEquals(3200.00, $payroll->grossSalary());
        $this->assertEquals(740.00, $payroll->totalDeductions()); // 640 tax + 100 other
        $this->assertEquals(2460.00, $payroll->net_salary);
    }

    public function test_payslip_html_shows_employee_gross_deductions_net(): void
    {
        $payroll = $this->payroll();

        $html = app(PayslipService::class)->html($payroll);

        $this->assertStringContainsString('Jane Doe', $html);
        $this->assertStringContainsString('3,200.00', $html);  // gross
        $this->assertStringContainsString('740.00', $html);    // total deductions
        $this->assertStringContainsString('2,460.00', $html);  // net
    }

    public function test_payslip_pdf_returns_bytes(): void
    {
        $payroll = $this->payroll();

        $pdf = app(PayslipService::class)->pdf($payroll);

        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
