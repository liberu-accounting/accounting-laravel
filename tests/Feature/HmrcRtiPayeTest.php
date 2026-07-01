<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HmrcPayeSubmission;
use App\Services\HmrcAuthService;
use App\Services\HmrcRtiPayeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HmrcRtiPayeTest extends TestCase
{
    use RefreshDatabase;

    protected HmrcRtiPayeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // HMRC OAuth token is provided by HmrcAuthService — stub it so no real auth call happens.
        $authService = $this->createStub(HmrcAuthService::class);
        $authService->method('getAccessToken')->willReturn('test-token');

        $this->service = new HmrcRtiPayeService($authService);
    }

    public function test_submits_fps_and_persists_submission_record(): void
    {
        $company = Company::factory()->create([
            'hmrc_paye_reference' => '123/AB12345',
        ]);

        $payeSubmission = HmrcPayeSubmission::create([
            'company_id' => $company->company_id,
            'tax_year' => '2023-24',
            'tax_month' => '1',
            'payment_date' => '2023-05-31',
            'employee_count' => 1,
            'employee_data' => [
                [
                    'name' => 'Jane Doe',
                    'nino' => 'AB123456C',
                    'gross_pay' => 2500.00,
                    'paye_tax' => 300.00,
                    'employee_ni' => 150.00,
                    'employer_ni' => 200.00,
                ],
            ],
        ]);

        // HMRC RTI returns an XML envelope; fake it so we never hit the real endpoint.
        Http::fake([
            '*/organisations/paye/*/fps' => Http::response(
                '<?xml version="1.0" encoding="UTF-8"?>'
                .'<GovTalkMessage><Header><MessageDetails>'
                .'<CorrelationID>RTI-CORR-123</CorrelationID>'
                .'<Timestamp>2023-05-31T10:00:00</Timestamp>'
                .'</MessageDetails></Header></GovTalkMessage>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $response = $this->service->submitFps($payeSubmission);

        $this->assertTrue($response['success']);
        $this->assertSame('RTI-CORR-123', $response['correlationId']);

        // FPS request was actually sent with the bearer token.
        Http::assertSent(fn ($request) => str_contains($request->url(), '/organisations/paye/123/AB12345/fps')
            && $request->hasHeader('Authorization', 'Bearer test-token'));

        // Submission row persisted and accepted.
        $this->assertDatabaseHas('hmrc_submissions', [
            'company_id' => $company->company_id,
            'submission_type' => 'paye_rti',
            'status' => 'accepted',
            'hmrc_reference' => 'RTI-CORR-123',
        ]);

        // PAYE submission linked back to the created HMRC submission.
        $payeSubmission->refresh();
        $this->assertNotNull($payeSubmission->hmrc_submission_id);
    }

    public function test_throws_when_company_has_no_paye_reference(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Company does not have a PAYE reference');

        $company = Company::factory()->create(['hmrc_paye_reference' => null]);

        $payeSubmission = HmrcPayeSubmission::create([
            'company_id' => $company->company_id,
            'tax_year' => '2023-24',
            'tax_month' => 1,
            'payment_date' => '2023-05-31',
            'employee_data' => [['name' => 'Jane Doe', 'nino' => 'AB123456C']],
        ]);

        $this->service->submitFps($payeSubmission);
    }

    /** A well-formed HMRC RTI XML envelope carrying a correlation id. */
    private function fakeRtiResponse(): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response(
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<GovTalkMessage><Header><MessageDetails>'
            .'<CorrelationID>RTI-CORR-999</CorrelationID>'
            .'<Timestamp>2023-05-31T10:00:00</Timestamp>'
            .'</MessageDetails></Header></GovTalkMessage>',
            200,
            ['Content-Type' => 'application/xml']
        );
    }

    private function payeSubmission(Company $company, array $overrides = []): HmrcPayeSubmission
    {
        return HmrcPayeSubmission::create(array_merge([
            'company_id' => $company->company_id,
            'tax_year' => '2023-24',
            'tax_month' => '1',
            'payment_date' => '2023-05-31',
            'employee_count' => 1,
            'employee_data' => [[
                'name' => 'Jane Doe',
                'nino' => 'AB123456C',
                'gross_pay' => 2500.00,
                'paye_tax' => 300.00,
                'employee_ni' => 150.00,
                'employer_ni' => 200.00,
            ]],
        ], $overrides));
    }

    public function test_throws_when_submission_has_no_employee_data(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('PAYE submission must have employee data');

        $company = Company::factory()->create(['hmrc_paye_reference' => '123/AB12345']);
        $submission = $this->payeSubmission($company, ['employee_data' => []]);

        $this->service->submitFps($submission);
    }

    public function test_submit_fps_throws_on_http_failure(): void
    {
        $company = Company::factory()->create(['hmrc_paye_reference' => '123/AB12345']);
        $submission = $this->payeSubmission($company);

        Http::fake(['*/organisations/paye/*/fps' => Http::response('<error/>', 500)]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to submit FPS');

        $this->service->submitFps($submission);
    }

    public function test_fps_xml_includes_student_loan_node_when_present(): void
    {
        $company = Company::factory()->create(['hmrc_paye_reference' => '123/AB12345']);
        $submission = $this->payeSubmission($company, ['employee_data' => [[
            'name' => 'Jane Doe',
            'nino' => 'AB123456C',
            'gross_pay' => 2500.00,
            'paye_tax' => 300.00,
            'employee_ni' => 150.00,
            'employer_ni' => 200.00,
            'student_loan' => 45.00,
        ]]]);

        Http::fake(['*/organisations/paye/*/fps' => $this->fakeRtiResponse()]);

        $this->service->submitFps($submission);

        Http::assertSent(fn ($request) => str_contains($request->body(), '<StudentLoan>45.00</StudentLoan>'));
    }

    public function test_fps_xml_omits_student_loan_node_when_absent(): void
    {
        $company = Company::factory()->create(['hmrc_paye_reference' => '123/AB12345']);
        $submission = $this->payeSubmission($company); // no student_loan key

        Http::fake(['*/organisations/paye/*/fps' => $this->fakeRtiResponse()]);

        $this->service->submitFps($submission);

        Http::assertSent(fn ($request) => ! str_contains($request->body(), '<StudentLoan>'));
    }

    public function test_tax_period_start_rolls_over_to_next_year_for_late_tax_months(): void
    {
        // tax_year 2023-24, tax_month 10 → April(1)+9 = month 13 → Jan of the next
        // calendar year: 2024-01-06.
        $company = Company::factory()->create(['hmrc_paye_reference' => '123/AB12345']);
        $submission = $this->payeSubmission($company, ['tax_month' => '10']);

        Http::fake(['*/organisations/paye/*/fps' => $this->fakeRtiResponse()]);

        $this->service->submitFps($submission);

        $this->assertDatabaseHas('hmrc_submissions', [
            'company_id' => $company->company_id,
            'submission_type' => 'paye_rti',
            'tax_period_from' => '2024-01-06 00:00:00',
        ]);
    }

    public function test_submit_eps_succeeds(): void
    {
        Http::fake(['*/organisations/paye/*/eps' => $this->fakeRtiResponse()]);

        $ok = $this->service->submitEps(['flag' => true], '123/AB12345');

        $this->assertTrue($ok['success']);
        $this->assertSame('RTI-CORR-999', $ok['correlationId']);
    }

    public function test_submit_eps_throws_on_failure(): void
    {
        Http::fake(['*/organisations/paye/*/eps' => Http::response('<error/>', 500)]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to submit EPS');

        $this->service->submitEps(['flag' => true], '123/AB12345');
    }

    public function test_submit_eyu_succeeds(): void
    {
        Http::fake(['*/organisations/paye/*/eyu' => $this->fakeRtiResponse()]);

        $ok = $this->service->submitEyu(['flag' => true], '123/AB12345');

        $this->assertTrue($ok['success']);
        $this->assertSame('RTI-CORR-999', $ok['correlationId']);
    }

    public function test_submit_eyu_throws_on_failure(): void
    {
        Http::fake(['*/organisations/paye/*/eyu' => Http::response('<error/>', 500)]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to submit EYU');

        $this->service->submitEyu(['flag' => true], '123/AB12345');
    }
}
