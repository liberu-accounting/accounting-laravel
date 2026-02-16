<?php

namespace App\Services;

use App\Models\HmrcPayeSubmission;
use App\Models\HmrcSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HmrcRtiPayeService
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
     * Submit Full Payment Submission (FPS) to HMRC.
     */
    public function submitFps(HmrcPayeSubmission $payeSubmission): array
    {
        $company = $payeSubmission->company;
        
        if (!$company->hmrc_paye_reference) {
            throw new \Exception('Company does not have a PAYE reference');
        }

        if (!$payeSubmission->employee_data || count($payeSubmission->employee_data) === 0) {
            throw new \Exception('PAYE submission must have employee data');
        }

        try {
            $xml = $this->buildFpsXml($payeSubmission);
            
            // Parse PAYE reference (format: 123/AB12345)
            [$officeNumber, $payeRef] = explode('/', $company->hmrc_paye_reference);
            
            $response = Http::withHeaders($this->getHeaders())
                ->withBody($xml, 'application/xml')
                ->post($this->baseUrl . "/organisations/paye/{$officeNumber}/{$payeRef}/fps");

            if ($response->successful()) {
                $responseData = $this->parseXmlResponse($response->body());
                
                // Create submission record
                $submission = HmrcSubmission::create([
                    'company_id' => $company->company_id,
                    'submission_type' => 'paye_rti',
                    'tax_period_from' => $this->getTaxPeriodStart($payeSubmission),
                    'tax_period_to' => $this->getTaxPeriodEnd($payeSubmission),
                    'status' => 'submitted',
                    'hmrc_reference' => $responseData['correlationId'] ?? null,
                    'submission_data' => ['xml' => $xml],
                    'response_data' => $responseData,
                    'submitted_at' => now(),
                ]);

                // Update PAYE submission with submission
                $payeSubmission->update([
                    'hmrc_submission_id' => $submission->id,
                ]);

                $submission->markAsAccepted();

                $this->logInfo('FPS submitted successfully', [
                    'paye_submission_id' => $payeSubmission->id,
                    'reference' => $submission->hmrc_reference,
                ]);

                return $responseData;
            }

            $this->logError('FPS submission failed', ['response' => $response->body()]);
            throw new \Exception('Failed to submit FPS: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('FPS submission error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Submit Employer Payment Summary (EPS) to HMRC.
     */
    public function submitEps(array $epsData, string $payeReference): array
    {
        try {
            $xml = $this->buildEpsXml($epsData);
            
            [$officeNumber, $payeRef] = explode('/', $payeReference);
            
            $response = Http::withHeaders($this->getHeaders())
                ->withBody($xml, 'application/xml')
                ->post($this->baseUrl . "/organisations/paye/{$officeNumber}/{$payeRef}/eps");

            if ($response->successful()) {
                $responseData = $this->parseXmlResponse($response->body());
                
                $this->logInfo('EPS submitted successfully', [
                    'reference' => $responseData['correlationId'] ?? null,
                ]);

                return $responseData;
            }

            $this->logError('EPS submission failed', ['response' => $response->body()]);
            throw new \Exception('Failed to submit EPS: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('EPS submission error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Submit Earlier Year Update (EYU) to HMRC.
     */
    public function submitEyu(array $eyuData, string $payeReference): array
    {
        try {
            $xml = $this->buildEyuXml($eyuData);
            
            [$officeNumber, $payeRef] = explode('/', $payeReference);
            
            $response = Http::withHeaders($this->getHeaders())
                ->withBody($xml, 'application/xml')
                ->post($this->baseUrl . "/organisations/paye/{$officeNumber}/{$payeRef}/eyu");

            if ($response->successful()) {
                $responseData = $this->parseXmlResponse($response->body());
                
                $this->logInfo('EYU submitted successfully', [
                    'reference' => $responseData['correlationId'] ?? null,
                ]);

                return $responseData;
            }

            $this->logError('EYU submission failed', ['response' => $response->body()]);
            throw new \Exception('Failed to submit EYU: ' . $response->body());

        } catch (\Exception $e) {
            $this->logError('EYU submission error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Build FPS XML for HMRC submission.
     */
    private function buildFpsXml(HmrcPayeSubmission $payeSubmission): string
    {
        $company = $payeSubmission->company;
        $schemaVersion = config('hmrc.rti.schema_version', '16-17');
        $namespace = config('hmrc.rti.namespace_base') . "/FullPaymentSubmission/{$schemaVersion}";
        
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><IRenvelope xmlns=\"{$namespace}\"></IRenvelope>");
        
        // Header
        $header = $xml->addChild('Header');
        $header->addChild('MessageDetails');
        $header->MessageDetails->addChild('Class', 'HMRC-PAYE-RTI-FPS');
        $header->MessageDetails->addChild('Qualifier', 'request');
        $header->MessageDetails->addChild('Function', 'submit');
        $header->MessageDetails->addChild('CorrelationID', $this->generateCorrelationId());
        
        // Sender
        $sender = $header->addChild('Sender');
        $sender->addChild('EmailAddress', $company->company_email);
        
        // Body
        $body = $xml->addChild('Body');
        $fps = $body->addChild('FullPaymentSubmission');
        
        // Employment
        $employment = $fps->addChild('Employment');
        $employment->addChild('TaxYear', $payeSubmission->tax_year);
        $employment->addChild('TaxMonth', $payeSubmission->tax_month);
        $employment->addChild('PaymentDate', $payeSubmission->payment_date->format('Y-m-d'));
        
        // Employer
        $employer = $employment->addChild('Employer');
        $employer->addChild('OfficeNumber', explode('/', $company->hmrc_paye_reference)[0]);
        $employer->addChild('PayeReference', explode('/', $company->hmrc_paye_reference)[1]);
        $employer->addChild('Name', $company->company_name);
        
        // Employees
        foreach ($payeSubmission->employee_data as $empData) {
            $employee = $employment->addChild('Employee');
            $employee->addChild('Name', $empData['name']);
            $employee->addChild('NINO', $empData['nino']);
            
            $payment = $employee->addChild('Payment');
            $payment->addChild('GrossPay', number_format($empData['gross_pay'], 2, '.', ''));
            $payment->addChild('TaxDeducted', number_format($empData['paye_tax'], 2, '.', ''));
            $payment->addChild('EmployeeNICs', number_format($empData['employee_ni'], 2, '.', ''));
            $payment->addChild('EmployerNICs', number_format($empData['employer_ni'], 2, '.', ''));
            
            if (!empty($empData['student_loan'])) {
                $payment->addChild('StudentLoan', number_format($empData['student_loan'], 2, '.', ''));
            }
        }
        
        return $xml->asXML();
    }

    /**
     * Build EPS XML for HMRC submission.
     */
    private function buildEpsXml(array $epsData): string
    {
        $schemaVersion = config('hmrc.rti.schema_version', '16-17');
        $namespace = config('hmrc.rti.namespace_base') . "/EmployerPaymentSummary/{$schemaVersion}";
        
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><IRenvelope xmlns=\"{$namespace}\"></IRenvelope>");
        
        // Build EPS structure similar to FPS
        // This is a simplified version - actual implementation would be more detailed
        
        return $xml->asXML();
    }

    /**
     * Build EYU XML for HMRC submission.
     */
    private function buildEyuXml(array $eyuData): string
    {
        $schemaVersion = config('hmrc.rti.schema_version', '16-17');
        $namespace = config('hmrc.rti.namespace_base') . "/EarlierYearUpdate/{$schemaVersion}";
        
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><IRenvelope xmlns=\"{$namespace}\"></IRenvelope>");
        
        // Build EYU structure
        // This is a simplified version - actual implementation would be more detailed
        
        return $xml->asXML();
    }

    /**
     * Parse XML response from HMRC.
     */
    private function parseXmlResponse(string $xmlString): array
    {
        $xml = simplexml_load_string($xmlString);
        
        return [
            'correlationId' => (string) ($xml->Header->MessageDetails->CorrelationID ?? ''),
            'timestamp' => (string) ($xml->Header->MessageDetails->Timestamp ?? ''),
            'success' => true,
        ];
    }

    /**
     * Generate unique correlation ID.
     */
    private function generateCorrelationId(): string
    {
        return strtoupper(uniqid('RTI'));
    }

    /**
     * Get tax period start date.
     */
    private function getTaxPeriodStart(HmrcPayeSubmission $payeSubmission): string
    {
        $year = (int) substr($payeSubmission->tax_year, 0, 4);
        $month = $payeSubmission->tax_month;
        
        // UK tax year starts in April
        $actualMonth = $month + 3; // April = month 1
        $actualYear = $actualMonth > 12 ? $year + 1 : $year;
        $actualMonth = $actualMonth > 12 ? $actualMonth - 12 : $actualMonth;
        
        return sprintf('%04d-%02d-06', $actualYear, $actualMonth);
    }

    /**
     * Get tax period end date.
     */
    private function getTaxPeriodEnd(HmrcPayeSubmission $payeSubmission): string
    {
        $start = new \DateTime($this->getTaxPeriodStart($payeSubmission));
        $end = clone $start;
        $end->modify('+1 month -1 day');
        
        return $end->format('Y-m-d');
    }

    /**
     * Get HTTP headers for HMRC API requests.
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->authService->getAccessToken(),
            'Accept' => 'application/xml',
            'Content-Type' => 'application/xml',
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
