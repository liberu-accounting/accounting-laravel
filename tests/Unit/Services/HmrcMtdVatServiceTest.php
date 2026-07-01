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

        $this->authService = $this->createStub(HmrcAuthService::class);
        $this->authService->method('getAccessToken')->willReturn('test-token');

        $this->service = new HmrcMtdVatService($this->authService);
    }

    public function test_builds_vat_return_payload_correctly(): void
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

    public function test_throws_exception_when_company_has_no_vat_number(): void
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

    public function test_throws_exception_when_vat_return_not_finalised(): void
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

    private function finalisedReturn(array $overrides = []): HmrcVatReturn
    {
        $company = Company::factory()->create(['hmrc_vat_number' => '123456789']);

        return HmrcVatReturn::create(array_merge([
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
        ], $overrides));
    }

    public function test_submit_vat_return_throws_on_http_failure(): void
    {
        $vatReturn = $this->finalisedReturn();

        Http::fake([
            '*/organisations/vat/*/returns' => Http::response(['code' => 'SERVER_ERROR'], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to submit VAT return');

        $this->service->submitVatReturn($vatReturn);
    }

    public function test_submit_vat_return_payload_uses_2dp_and_whole_number_formatting(): void
    {
        $vatReturn = $this->finalisedReturn([
            'vat_due_sales' => 1000.55,
            'total_value_sales' => 5000.99,
        ]);

        Http::fake([
            '*/organisations/vat/*/returns' => Http::response(['formBundleNumber' => 'X'], 200),
        ]);

        $this->service->submitVatReturn($vatReturn);

        Http::assertSent(fn ($request) => $request['vatDueSales'] === 1000.55        // formatAmount → 2dp float
            && $request['totalValueSalesExVAT'] === 5001                              // formatWholeAmount → int, rounded
            && $request['finalised'] === true);
    }

    public function test_get_obligations_returns_json_on_success(): void
    {
        Http::fake([
            '*/organisations/vat/*/obligations*' => Http::response(['obligations' => [['status' => 'O']]], 200),
        ]);

        $result = $this->service->getObligations('123456789', '2023-01-01', '2023-12-31');

        $this->assertSame([['status' => 'O']], $result['obligations']);
    }

    public function test_get_obligations_throws_on_failure(): void
    {
        Http::fake([
            '*/organisations/vat/*/obligations*' => Http::response(['code' => 'NOT_FOUND'], 404),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve VAT obligations');

        $this->service->getObligations('123456789', '2023-01-01', '2023-12-31');
    }

    public function test_get_vat_return_returns_json_on_success(): void
    {
        Http::fake([
            '*/organisations/vat/*/returns/*' => Http::response(['periodKey' => '23A1'], 200),
        ]);

        $result = $this->service->getVatReturn('123456789', '23A1');

        $this->assertSame('23A1', $result['periodKey']);
    }

    public function test_get_vat_return_throws_on_failure(): void
    {
        Http::fake([
            '*/organisations/vat/*/returns/*' => Http::response(['code' => 'NOT_FOUND'], 404),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve VAT return');

        $this->service->getVatReturn('123456789', '23A1');
    }

    public function test_get_liabilities_returns_json_on_success(): void
    {
        Http::fake([
            '*/organisations/vat/*/liabilities*' => Http::response(['liabilities' => []], 200),
        ]);

        $result = $this->service->getLiabilities('123456789', '2023-01-01', '2023-12-31');

        $this->assertArrayHasKey('liabilities', $result);
    }

    public function test_get_liabilities_throws_on_failure(): void
    {
        Http::fake([
            '*/organisations/vat/*/liabilities*' => Http::response([], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve VAT liabilities');

        $this->service->getLiabilities('123456789', '2023-01-01', '2023-12-31');
    }

    public function test_get_payments_returns_json_on_success(): void
    {
        Http::fake([
            '*/organisations/vat/*/payments*' => Http::response(['payments' => []], 200),
        ]);

        $result = $this->service->getPayments('123456789', '2023-01-01', '2023-12-31');

        $this->assertArrayHasKey('payments', $result);
    }

    public function test_get_payments_throws_on_failure(): void
    {
        Http::fake([
            '*/organisations/vat/*/payments*' => Http::response([], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve VAT payments');

        $this->service->getPayments('123456789', '2023-01-01', '2023-12-31');
    }
}
