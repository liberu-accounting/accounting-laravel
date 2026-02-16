<?php

namespace App\Services;

use App\Models\HmrcCorporationTaxSubmission;
use App\Models\HmrcSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HmrcMtdCorporationTaxService
{
    private string $baseUrl;
    private HmrcAuthService $authService;

    public function __construct(HmrcAuthService $authService)
    {
        $environment = config('hmrc.environment');
        $this->baseUrl = config("hmrc.endpoints.{$environment}");
        $this->authService = $authService;
    }

    /**
     * Submit corporation tax computation to HMRC.
     */
    public function submitComputation(HmrcCorporationTaxSubmission $ctSubmission): array
    {
        $company = $ctSubmission->company;
        
        if (!$company->hmrc_corporation_tax_utr) {
            throw new \Exception('Company does not have a Corporation Tax UTR');
        }

        try {
            $payload = $this->buildComputationPayload($ctSubmission);
            
            $response = Http::withHeaders($this->getHeaders())
                ->post(
                    $this->baseUrl . "/organisations/corporation-tax/{$company->hmrc_corporation_tax_utr}/computations",
                    $payload
                );

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Create submission record
                $submission = HmrcSubmission::create([
                    'company_id' => $company->company_id,
                    'submission_type' => 'corporation_tax',
                    'tax_period_from' => $ctSubmission->accounting_period_start,
                    'tax_period_to' => $ctSubmission->accounting_period_end,
                    'status' => 'submitted',
                    'hmrc_reference' => $responseData['referenceNumber'] ?? null,
                    'submission_data' => $payload,
                    'response_data' => $responseData,
                    'submitted_at' => now(),
                ]);

                // Update CT submission with submission
                $ctSubmission->update([
                    'hmrc_submission_id' => $submission->id,
                ]);

                $submission->markAsAccepted();

                $this->logInfo('Corporation tax computation submitted successfully', [
                    'ct_submission_id' => $ctSubmission->id,
                    'reference' => $submission->hmrc_reference,
                ]);

                return $responseData;
            }

            $this->logError('CT computation submission failed', $response->json());
            throw new \Exception('Failed to submit corporation tax computation: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('CT computation submission error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retrieve corporation tax obligations.
     */
    public function getObligations(string $utr, string $from, string $to): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . "/organisations/corporation-tax/{$utr}/obligations", [
                    'from' => $from,
                    'to' => $to,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            $this->logError('Failed to retrieve CT obligations', $response->json());
            throw new \Exception('Failed to retrieve corporation tax obligations: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('CT obligations error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retrieve corporation tax liabilities.
     */
    public function getLiabilities(string $utr, string $from, string $to): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . "/organisations/corporation-tax/{$utr}/liabilities", [
                    'from' => $from,
                    'to' => $to,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            $this->logError('Failed to retrieve CT liabilities', $response->json());
            throw new \Exception('Failed to retrieve corporation tax liabilities: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('CT liabilities error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retrieve corporation tax payments.
     */
    public function getPayments(string $utr, string $from, string $to): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . "/organisations/corporation-tax/{$utr}/payments", [
                    'from' => $from,
                    'to' => $to,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            $this->logError('Failed to retrieve CT payments', $response->json());
            throw new \Exception('Failed to retrieve corporation tax payments: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('CT payments error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Build corporation tax computation payload.
     */
    private function buildComputationPayload(HmrcCorporationTaxSubmission $ctSubmission): array
    {
        return [
            'accountingPeriod' => [
                'startDate' => $ctSubmission->accounting_period_start,
                'endDate' => $ctSubmission->accounting_period_end,
            ],
            'turnover' => $this->formatAmount($ctSubmission->turnover),
            'totalProfit' => $this->formatAmount($ctSubmission->total_profits),
            'taxableProfit' => $this->formatAmount($ctSubmission->taxable_profits),
            'corporationTax' => [
                'charged' => $this->formatAmount($ctSubmission->corporation_tax_charged),
                'marginalRelief' => $this->formatAmount($ctSubmission->marginal_relief),
                'totalPayable' => $this->formatAmount($ctSubmission->total_tax_payable),
            ],
            'filingDueDate' => $ctSubmission->filing_due_date->format('Y-m-d'),
            'paymentDueDate' => $ctSubmission->payment_due_date->format('Y-m-d'),
            'isAmended' => $ctSubmission->is_amended,
            'computationDetails' => $ctSubmission->computation_data,
        ];
    }

    /**
     * Format amount to 2 decimal places.
     */
    private function formatAmount($amount): float
    {
        return round((float) $amount, 2);
    }

    /**
     * Get HTTP headers for HMRC API requests.
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->authService->getAccessToken(),
            'Accept' => 'application/vnd.hmrc.1.0+json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Log info if logging is enabled.
     */
    private function logInfo(string $message, array $context = []): void
    {
        if (config('hmrc.logging.enabled')) {
            Log::channel(config('hmrc.logging.channel'))->info($message, $context);
        }
    }

    /**
     * Log error if logging is enabled.
     */
    private function logError(string $message, array $context = []): void
    {
        if (config('hmrc.logging.enabled')) {
            Log::channel(config('hmrc.logging.channel'))->error($message, $context);
        }
    }
}
