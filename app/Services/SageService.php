<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\SageConnection;
use App\Services\Concerns\RequestsProviderTokens;
use App\Services\Concerns\ResolvesSyncedContacts;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Two-way sync with Sage Accounting via OAuth 2.0. Shares the OAuth token
 * request and contact-resolution helpers with QBO/Xero via the Concerns traits;
 * provider-specific endpoints/payloads stay here.
 */
class SageService
{
    use RequestsProviderTokens;
    use ResolvesSyncedContacts;

    /** @var array<string, mixed> */
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('services.sage');
    }

    public function getAuthorizationUrl(string $state): string
    {
        return $this->cfg['authorization_url'].'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->cfg['client_id'],
            'redirect_uri' => $this->cfg['redirect_uri'],
            'scope' => 'full_access',
            'state' => $state,
        ]);
    }

    /**
     * Exchange the code for tokens, resolve the Sage business, persist the connection.
     */
    public function handleCallback(int $userId, string $code): SageConnection
    {
        $tokens = $this->requestProviderTokens($this->cfg, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->cfg['redirect_uri'],
        ]);

        $businessId = Http::withToken($tokens['access_token'])
            ->acceptJson()
            ->get($this->cfg['businesses_url'])
            ->throw()
            ->json()['$items'][0]['id'] ?? null;

        return SageConnection::updateOrCreate(
            ['user_id' => $userId, 'business_id' => $businessId],
            [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
                'status' => 'active',
            ],
        );
    }

    public function pushInvoice(Invoice $invoice, SageConnection $connection): Invoice
    {
        $payload = ['sales_invoice' => [
            'contact_id' => (string) ($invoice->customer_id ?? ''),
            'date' => optional($invoice->invoice_date)->format('Y-m-d') ?? (string) $invoice->invoice_date,
            'invoice_lines' => [[
                'description' => 'Invoice '.($invoice->invoice_number ?? $invoice->id),
                'quantity' => 1,
                'unit_price' => (float) $invoice->total_amount,
            ]],
        ]];

        $body = $this->client($connection)->post('/sales_invoices', $payload)->throw()->json();

        $invoice->update(['sage_id' => $body['id'] ?? $invoice->sage_id]);

        return $invoice;
    }

    public function pullInvoices(SageConnection $connection): int
    {
        $rows = $this->client($connection)->get('/sales_invoices')->throw()->json()['$items'] ?? [];

        foreach ($rows as $row) {
            Invoice::updateOrCreate(
                ['sage_id' => $row['id']],
                [
                    'invoice_number' => $row['displayed_as'] ?? ('SAGE-'.$row['id']),
                    'customer_id' => $this->resolveCustomerId($row['contact_name'] ?? null),
                    'total_amount' => $row['total_amount'] ?? 0,
                    'invoice_date' => $row['date'] ?? now()->toDateString(),
                    'payment_status' => 'pending',
                ],
            );
        }

        $connection->update(['last_synced_at' => now()]);

        return count($rows);
    }

    private function client(SageConnection $connection): PendingRequest
    {
        return Http::withToken($connection->access_token)
            ->acceptJson()
            ->baseUrl($this->cfg['api_base_url']);
    }

    private function resolveCustomerId(?string $contactName): int
    {
        return $this->syncedCustomerId($contactName ?? 'Sage Customer', 'sage');
    }
}
