<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\XeroConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Two-way sync with Xero via OAuth 2.0. Mirrors QuickBooksService — kept as a
 * separate service rather than a shared base; extract a common contract if a
 * third accounting provider lands.
 */
class XeroService
{
    /** @var array<string, mixed> */
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('services.xero');
    }

    public function getAuthorizationUrl(string $state): string
    {
        return $this->cfg['authorization_url'].'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->cfg['client_id'],
            'redirect_uri' => $this->cfg['redirect_uri'],
            'scope' => 'offline_access accounting.transactions accounting.contacts',
            'state' => $state,
        ]);
    }

    /**
     * Exchange the code for tokens, resolve the Xero tenant, persist the connection.
     */
    public function handleCallback(int $userId, string $code): XeroConnection
    {
        $tokens = Http::asForm()
            ->withBasicAuth($this->cfg['client_id'], $this->cfg['client_secret'])
            ->post($this->cfg['token_url'], [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->cfg['redirect_uri'],
            ])->throw()->json();

        $tenantId = Http::withToken($tokens['access_token'])
            ->acceptJson()
            ->get($this->cfg['connections_url'])
            ->throw()
            ->json()[0]['tenantId'] ?? null;

        return XeroConnection::updateOrCreate(
            ['user_id' => $userId, 'tenant_id' => $tenantId],
            [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 1800)),
                'status' => 'active',
            ],
        );
    }

    public function pushInvoice(Invoice $invoice, XeroConnection $connection): Invoice
    {
        $payload = ['Invoices' => [[
            'Type' => 'ACCREC',
            'Contact' => ['Name' => $invoice->customer?->customer_name ?? ('Customer '.$invoice->customer_id)],
            'LineItems' => [[
                'Description' => 'Invoice '.($invoice->invoice_number ?? $invoice->id),
                'Quantity' => 1,
                'UnitAmount' => (float) $invoice->total_amount,
                'AccountCode' => '200',
            ]],
            'Status' => 'AUTHORISED',
        ]]];

        $body = $this->client($connection)->post('/Invoices', $payload)->throw()->json();

        $invoice->update(['xero_id' => $body['Invoices'][0]['InvoiceID'] ?? $invoice->xero_id]);

        return $invoice;
    }

    public function pullInvoices(XeroConnection $connection): int
    {
        $rows = $this->client($connection)->get('/Invoices')->throw()->json()['Invoices'] ?? [];

        foreach ($rows as $row) {
            Invoice::updateOrCreate(
                ['xero_id' => $row['InvoiceID']],
                [
                    'invoice_number' => $row['InvoiceNumber'] ?? ('XERO-'.$row['InvoiceID']),
                    'customer_id' => $this->resolveCustomerId($row['Contact'] ?? null),
                    'total_amount' => $row['Total'] ?? 0,
                    'invoice_date' => $row['Date'] ?? now()->toDateString(),
                    'payment_status' => 'pending',
                ],
            );
        }

        $connection->update(['last_synced_at' => now()]);

        return count($rows);
    }

    private function client(XeroConnection $connection): PendingRequest
    {
        return Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->acceptJson()
            ->baseUrl($this->cfg['api_base_url']);
    }

    /**
     * @param  array<string, mixed>|null  $contact
     */
    private function resolveCustomerId(?array $contact): int
    {
        $name = $contact['Name'] ?? 'Xero Customer';
        $ref = Str::random(8);

        $customer = Customer::firstOrCreate(
            ['customer_name' => $name],
            [
                'customer_last_name' => '',
                'customer_address' => 'Imported from Xero',
                'customer_email' => Str::slug($name).'.'.$ref.'@xero.imported',
                'customer_phone' => 'xero-'.$ref,
                'customer_city' => 'Unknown',
            ],
        );

        return (int) $customer->getKey();
    }
}
