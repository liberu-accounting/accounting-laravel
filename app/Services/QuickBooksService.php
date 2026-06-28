<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\QboConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Two-way sync with QuickBooks Online via OAuth 2.0.
 *
 * Mirrors the PlaidService pattern: hand-rolled HTTP through the Laravel client,
 * config under services.qbo, encrypted tokens on QboConnection.
 *
 * ponytail: Account and Bill/Payment mapping follow the same client() pattern as
 * pushInvoice/pullInvoices — add them when those round-trips are needed. R1's
 * acceptance gate is an invoice round-trip, so that is what ships verified first.
 */
class QuickBooksService
{
    /** @var array<string, mixed> */
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('services.qbo');
    }

    public function getAuthorizationUrl(string $state): string
    {
        return $this->cfg['authorization_url'].'?'.http_build_query([
            'client_id' => $this->cfg['client_id'],
            'response_type' => 'code',
            'scope' => 'com.intuit.quickbooks.accounting',
            'redirect_uri' => $this->cfg['redirect_uri'],
            'state' => $state,
        ]);
    }

    /**
     * Exchange an authorization code for tokens and persist the connection.
     */
    public function handleCallback(int $userId, string $code, string $realmId): QboConnection
    {
        $tokens = $this->requestTokens([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->cfg['redirect_uri'],
        ]);

        return QboConnection::updateOrCreate(
            ['user_id' => $userId, 'realm_id' => $realmId],
            [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
                'status' => 'active',
            ],
        );
    }

    /**
     * Push a local invoice to QBO. Creates if unmapped, sparse-updates if already synced.
     */
    public function pushInvoice(Invoice $invoice, QboConnection $connection): Invoice
    {
        $payload = [
            'Line' => [[
                'Amount' => (float) $invoice->total_amount,
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => ['ItemRef' => ['value' => '1']],
            ]],
            'CustomerRef' => ['value' => (string) ($invoice->customer?->qbo_id ?? $invoice->customer_id ?? '1')],
        ];

        if ($invoice->qbo_id) {
            $payload['Id'] = $invoice->qbo_id;
            $payload['SyncToken'] = $invoice->qbo_sync_token ?? '0';
            $payload['sparse'] = true;
        }

        $body = $this->client($connection)->post('/invoice', $payload)->throw()->json();

        $invoice->update([
            'qbo_id' => $body['Invoice']['Id'] ?? $invoice->qbo_id,
            'qbo_sync_token' => $body['Invoice']['SyncToken'] ?? $invoice->qbo_sync_token,
        ]);

        return $invoice;
    }

    /**
     * Pull invoices from QBO into local records. Returns the number processed.
     */
    public function pullInvoices(QboConnection $connection): int
    {
        $body = $this->client($connection)
            ->get('/query', ['query' => 'select * from Invoice'])
            ->throw()
            ->json();

        $rows = $body['QueryResponse']['Invoice'] ?? [];

        foreach ($rows as $row) {
            Invoice::updateOrCreate(
                ['qbo_id' => $row['Id']],
                [
                    'invoice_number' => $row['DocNumber'] ?? ('QBO-'.$row['Id']),
                    'customer_id' => $this->resolveCustomerId($row['CustomerRef'] ?? null),
                    'total_amount' => $row['TotalAmt'] ?? 0,
                    'invoice_date' => $row['TxnDate'] ?? now()->toDateString(),
                    'qbo_sync_token' => $row['SyncToken'] ?? null,
                    'payment_status' => 'pending',
                ],
            );
        }

        $connection->update(['last_synced_at' => now()]);

        return count($rows);
    }

    /**
     * Map a QBO CustomerRef onto a local customer, creating one if unseen.
     * The invoices table requires customer_id, so pulled invoices need a customer.
     *
     * @param  array<string, mixed>|null  $customerRef
     */
    private function resolveCustomerId(?array $customerRef): int
    {
        $name = $customerRef['name'] ?? ('QBO Customer '.($customerRef['value'] ?? 'Unknown'));

        $ref = (string) ($customerRef['value'] ?? Str::random(8));

        $customer = Customer::firstOrCreate(
            ['customer_name' => $name],
            [
                'customer_last_name' => '',
                'customer_address' => 'Imported from QuickBooks Online',
                'customer_email' => Str::slug($name).'.'.$ref.'@qbo.imported',
                'customer_phone' => 'qbo-'.$ref,
                'customer_city' => 'Unknown',
            ],
        );

        return (int) $customer->getKey();
    }

    /**
     * An authenticated HTTP client scoped to the connection's QBO company.
     */
    private function client(QboConnection $connection): PendingRequest
    {
        $connection = $this->refreshIfNeeded($connection);

        return Http::withToken($connection->access_token)
            ->acceptJson()
            ->baseUrl($this->cfg['api_base_url'].'/v3/company/'.$connection->realm_id);
    }

    private function refreshIfNeeded(QboConnection $connection): QboConnection
    {
        if ($connection->token_expires_at && $connection->token_expires_at->isFuture()) {
            return $connection;
        }

        $tokens = $this->requestTokens([
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
        ]);

        $connection->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
        ]);

        return $connection;
    }

    /**
     * @param  array<string, string>  $form
     * @return array<string, mixed>
     */
    private function requestTokens(array $form): array
    {
        return Http::asForm()
            ->withBasicAuth($this->cfg['client_id'], $this->cfg['client_secret'])
            ->post($this->cfg['token_url'], $form)
            ->throw()
            ->json();
    }
}
