<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\HmrcVatReturn;
use App\Services\HmrcAuthService;
use App\Services\HmrcMtdVatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HmrcMtdVatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HmrcMtdVatService $service;
    protected HmrcAuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authService = $this->createMock(HmrcAuthService::class);
        $this->authService->method('getAccessToken')->willReturn('test-token');
        
        $this->service = new HmrcMtdVatService($this->authService);
    }

    public function test_builds_vat_return_payload_correctly()
    {
        $company = Company::factory()->create([
            'hmrc_vat_number' => '123456789',
        ]);

        $vatReturn = HmrcVatReturn::create([
            'company_id' => $company->company_id,
            'period_key' => '23A1',
            'period_from' => '2023-01-01',
            'period_to' => '2023-03-31',
            'due_date' => '2023-05-07',
            'vat_due_sales' => 1000.00,
            'vat_due_acquisitions' => 0,
            'total_vat_due' => 1000.00,
            'vat_reclaimed' => 500.00,
            'net_vat_due' => 500.00,
            'total_value_sales' => 5000.00,
            'total_value_purchases' => 2500.00,
            'total_value_goods_supplied' => 0,
            'total_acquisitions' => 0,
            'finalised' => true,
        ]);

        Http::fake([
            '*/organisations/vat/*/returns' => Http::response([
                'formBundleNumber' => 'TEST123456',
                'chargeRefNumber' => 'TEST789',
            ], 200),
        ]);

        $response = $this->service->submitVatReturn($vatReturn);

        $this->assertArrayHasKey('formBundleNumber', $response);
        $this->assertEquals('TEST123456', $response['formBundleNumber']);
    }

    public function test_throws_exception_when_company_has_no_vat_number()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Company does not have a VAT registration number');

        $company = Company::factory()->create([
            'hmrc_vat_number' => null,
        ]);

        $vatReturn = HmrcVatReturn::create([
            'company_id' => $company->company_id,
            'period_key' => '23A1',
            'period_from' => '2023-01-01',
            'period_to' => '2023-03-31',
            'due_date' => '2023-05-07',
            'finalised' => true,
        ]);

        $this->service->submitVatReturn($vatReturn);
    }

    public function test_throws_exception_when_vat_return_not_finalised()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('VAT return must be finalised before submission');

        $company = Company::factory()->create([
            'hmrc_vat_number' => '123456789',
        ]);

        $vatReturn = HmrcVatReturn::create([
            'company_id' => $company->company_id,
            'period_key' => '23A1',
            'period_from' => '2023-01-01',
            'period_to' => '2023-03-31',
            'due_date' => '2023-05-07',
            'finalised' => false,
        ]);

        $this->service->submitVatReturn($vatReturn);
    }
}
