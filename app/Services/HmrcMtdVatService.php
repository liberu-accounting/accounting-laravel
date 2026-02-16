<?php

namespace App\Services;

use App\Models\HmrcVatReturn;
use App\Models\HmrcSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HmrcMtdVatService
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
     * Retrieve VAT obligations for a given period.
     */
    public function getObligations(string $vrn, string $from, string $to): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . "/organisations/vat/{$vrn}/obligations", [
                    'from' => $from,
                    'to' => $to,
                    'status' => 'O', // O = Open, F = Fulfilled
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            $this->logError('Failed to retrieve VAT obligations', $response->json());
            throw new \Exception('Failed to retrieve VAT obligations: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('VAT obligations error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Submit VAT return to HMRC.
     */
    public function submitVatReturn(HmrcVatReturn $vatReturn): array
    {
        $company = $vatReturn->company;
        
        if (!$company->hmrc_vat_number) {
            throw new \Exception('Company does not have a VAT registration number');
        }

        if (!$vatReturn->finalised) {
            throw new \Exception('VAT return must be finalised before submission');
        }

        try {
            $payload = $this->buildVatReturnPayload($vatReturn);
            
            $response = Http::withHeaders($this->getHeaders())
                ->post(
                    $this->baseUrl . "/organisations/vat/{$company->hmrc_vat_number}/returns",
                    $payload
                );

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Create submission record
                $submission = HmrcSubmission::create([
                    'company_id' => $company->company_id,
                    'submission_type' => 'vat_return',
                    'tax_period_from' => $vatReturn->period_from->format('Y-m-d'),
                    'tax_period_to' => $vatReturn->period_to->format('Y-m-d'),
                    'status' => 'submitted',
                    'hmrc_reference' => $responseData['formBundleNumber'] ?? null,
                    'submission_data' => $payload,
                    'response_data' => $responseData,
                    'submitted_at' => now(),
                ]);

                // Update VAT return with submission
                $vatReturn->update([
                    'hmrc_submission_id' => $submission->id,
                ]);

                $submission->markAsAccepted();

                $this->logInfo('VAT return submitted successfully', [
                    'vat_return_id' => $vatReturn->id,
                    'reference' => $submission->hmrc_reference,
                ]);

                return $responseData;
            }

            $this->logError('VAT return submission failed', $response->json());
            throw new \Exception('Failed to submit VAT return: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('VAT return submission error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retrieve submitted VAT return from HMRC.
     */
    public function getVatReturn(string $vrn, string $periodKey): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . "/organisations/vat/{$vrn}/returns/{$periodKey}");

            if ($response->successful()) {
                return $response->json();
            }

            $this->logError('Failed to retrieve VAT return', $response->json());
            throw new \Exception('Failed to retrieve VAT return: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('VAT return retrieval error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retrieve VAT liabilities for a given period.
     */
    public function getLiabilities(string $vrn, string $from, string $to): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . "/organisations/vat/{$vrn}/liabilities", [
                    'from' => $from,
                    'to' => $to,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            $this->logError('Failed to retrieve VAT liabilities', $response->json());
            throw new \Exception('Failed to retrieve VAT liabilities: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('VAT liabilities error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Retrieve VAT payments for a given period.
     */
    public function getPayments(string $vrn, string $from, string $to): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . "/organisations/vat/{$vrn}/payments", [
                    'from' => $from,
                    'to' => $to,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            $this->logError('Failed to retrieve VAT payments', $response->json());
            throw new \Exception('Failed to retrieve VAT payments: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('VAT payments error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Build VAT return payload for HMRC submission.
     */
    private function buildVatReturnPayload(HmrcVatReturn $vatReturn): array
    {
        return [
            'periodKey' => $vatReturn->period_key,
            'vatDueSales' => $this->formatAmount($vatReturn->vat_due_sales),
            'vatDueAcquisitions' => $this->formatAmount($vatReturn->vat_due_acquisitions),
            'totalVatDue' => $this->formatAmount($vatReturn->total_vat_due),
            'vatReclaimedCurrPeriod' => $this->formatAmount($vatReturn->vat_reclaimed),
            'netVatDue' => $this->formatAmount($vatReturn->net_vat_due),
            'totalValueSalesExVAT' => $this->formatWholeAmount($vatReturn->total_value_sales),
            'totalValuePurchasesExVAT' => $this->formatWholeAmount($vatReturn->total_value_purchases),
            'totalValueGoodsSuppliedExVAT' => $this->formatWholeAmount($vatReturn->total_value_goods_supplied),
            'totalAcquisitionsExVAT' => $this->formatWholeAmount($vatReturn->total_acquisitions),
            'finalised' => $vatReturn->finalised,
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
     * Format amount to whole number (no decimals).
     */
    private function formatWholeAmount($amount): int
    {
        return (int) round((float) $amount);
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
