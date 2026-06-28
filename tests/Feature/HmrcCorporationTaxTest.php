<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HmrcCorporationTaxSubmission;
use App\Services\HmrcAuthService;
use App\Services\HmrcMtdCorporationTaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HmrcCorporationTaxTest extends TestCase
{
    use RefreshDatabase;

    private HmrcMtdCorporationTaxService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Stub the HMRC OAuth token so no real auth call happens.
        $authService = $this->createStub(HmrcAuthService::class);
        $authService->method('getAccessToken')->willReturn('test-token');

        $this->service = new HmrcMtdCorporationTaxService($authService);
    }

    private function submissionFor(Company $company): HmrcCorporationTaxSubmission
    {
        return HmrcCorporationTaxSubmission::create([
            'company_id' => $company->company_id,
            'accounting_period_start' => '2025-04-01',
            'accounting_period_end' => '2026-03-31',
            'turnover' => 500000,
            'total_profits' => 120000,
            'taxable_profits' => 120000,
            'corporation_tax_charged' => 30000,
            'marginal_relief' => 0,
            'total_tax_payable' => 30000,
            'computation_data' => ['note' => 'test'],
            'filing_due_date' => '2027-03-31',
            'payment_due_date' => '2027-01-01',
            'is_amended' => false,
        ]);
    }

    public function test_submits_computation_and_persists_submission_record(): void
    {
        $company = Company::factory()->create(['hmrc_corporation_tax_utr' => '1234567890']);
        $ct = $this->submissionFor($company);

        Http::fake([
            '*/organisations/corporation-tax/*/computations' => Http::response([
                'referenceNumber' => 'CT-REF-9001',
            ], 200),
        ]);

        $response = $this->service->submitComputation($ct);

        $this->assertSame('CT-REF-9001', $response['referenceNumber']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/organisations/corporation-tax/1234567890/computations')
            && $request->hasHeader('Authorization', 'Bearer test-token'));

        $this->assertDatabaseHas('hmrc_submissions', [
            'company_id' => $company->company_id,
            'submission_type' => 'corporation_tax',
            'hmrc_reference' => 'CT-REF-9001',
        ]);

        $this->assertNotNull($ct->fresh()->hmrc_submission_id);
    }

    public function test_throws_when_company_has_no_utr(): void
    {
        $this->expectException(\Exception::class);

        $company = Company::factory()->create(['hmrc_corporation_tax_utr' => null]);

        $this->service->submitComputation($this->submissionFor($company));
    }
}
