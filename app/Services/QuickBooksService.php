<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\QboConnection;
use App\Models\Vendor;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Two-way sync with QuickBooks Online via OAuth 2.0.
 *
 * Mirrors the PlaidService pattern: hand-rolled HTTP through the Laravel client,
 * config under services.qbo, encrypted tokens on QboConnection.
 *
 * Invoices, accounts and bills round-trip both directions. Payment mapping is the
 * remaining deferred entity — same client() pattern when it's needed.
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

    /** QBO Account.Classification → local account_type. */
    private const QBO_CLASSIFICATION = [
        'Asset' => 'asset',
        'Liability' => 'liability',
        'Equity' => 'equity',
        'Revenue' => 'revenue',
        'Expense' => 'expense',
    ];

    /** local account_type → QBO AccountType. */
    private const QBO_ACCOUNT_TYPE = [
        'asset' => 'Other Current Asset',
        'liability' => 'Other Current Liability',
        'equity' => 'Equity',
        'revenue' => 'Income',
        'income' => 'Income',
        'expense' => 'Expense',
    ];

    public function pushAccount(Account $account, QboConnection $connection): Account
    {
        $payload = [
            'Name' => $account->account_name,
            'AccountType' => self::QBO_ACCOUNT_TYPE[$account->account_type] ?? 'Other Current Asset',
        ];

        if ($account->qbo_id) {
            $payload['Id'] = $account->qbo_id;
            $payload['SyncToken'] = $account->qbo_sync_token ?? '0';
            $payload['sparse'] = true;
        }

        $body = $this->client($connection)->post('/account', $payload)->throw()->json();

        $account->update([
            'qbo_id' => $body['Account']['Id'] ?? $account->qbo_id,
            'qbo_sync_token' => $body['Account']['SyncToken'] ?? $account->qbo_sync_token,
        ]);

        return $account;
    }

    public function pullAccounts(QboConnection $connection): int
    {
        $rows = $this->query($connection, 'select * from Account')['Account'] ?? [];

        foreach ($rows as $row) {
            Account::updateOrCreate(
                ['qbo_id' => $row['Id']],
                [
                    'account_name' => $row['Name'] ?? ('QBO Account '.$row['Id']),
                    'account_type' => self::QBO_CLASSIFICATION[$row['Classification'] ?? ''] ?? 'asset',
                    'account_number' => isset($row['AcctNum']) && is_numeric($row['AcctNum'])
                        ? (int) $row['AcctNum']
                        : 9000 + (int) $row['Id'],
                    'qbo_sync_token' => $row['SyncToken'] ?? null,
                ],
            );
        }

        $connection->update(['last_synced_at' => now()]);

        return count($rows);
    }

    public function pushBill(Bill $bill, QboConnection $connection): Bill
    {
        $payload = [
            'VendorRef' => ['value' => (string) $bill->vendor_id],
            'Line' => [[
                'Amount' => (float) $bill->total_amount,
                'DetailType' => 'AccountBasedExpenseLineDetail',
                'AccountBasedExpenseLineDetail' => [
                    'AccountRef' => ['value' => (string) ($bill->items->first()->account_id ?? '1')],
                ],
            ]],
        ];

        if ($bill->qbo_id) {
            $payload['Id'] = $bill->qbo_id;
            $payload['SyncToken'] = $bill->qbo_sync_token ?? '0';
            $payload['sparse'] = true;
        }

        $body = $this->client($connection)->post('/bill', $payload)->throw()->json();

        $bill->update([
            'qbo_id' => $body['Bill']['Id'] ?? $bill->qbo_id,
            'qbo_sync_token' => $body['Bill']['SyncToken'] ?? $bill->qbo_sync_token,
        ]);

        return $bill;
    }

    public function pullBills(QboConnection $connection): int
    {
        $rows = $this->query($connection, 'select * from Bill')['Bill'] ?? [];

        foreach ($rows as $row) {
            Bill::updateOrCreate(
                ['qbo_id' => $row['Id']],
                [
                    'vendor_id' => $this->resolveVendorId($row['VendorRef'] ?? null),
                    'total_amount' => $row['TotalAmt'] ?? 0,
                    'bill_date' => $row['TxnDate'] ?? now()->toDateString(),
                    'due_date' => $row['DueDate'] ?? ($row['TxnDate'] ?? now()->toDateString()),
                    'qbo_sync_token' => $row['SyncToken'] ?? null,
                ],
            );
        }

        $connection->update(['last_synced_at' => now()]);

        return count($rows);
    }

    /**
     * Run a QBO query and return the QueryResponse payload.
     *
     * @return array<string, mixed>
     */
    private function query(QboConnection $connection, string $query): array
    {
        return $this->client($connection)
            ->get('/query', ['query' => $query])
            ->throw()
            ->json()['QueryResponse'] ?? [];
    }

    /**
     * Map a QBO VendorRef onto a local vendor, creating one if unseen.
     *
     * @param  array<string, mixed>|null  $vendorRef
     */
    private function resolveVendorId(?array $vendorRef): int
    {
        $name = $vendorRef['name'] ?? ('QBO Vendor '.($vendorRef['value'] ?? 'Unknown'));
        $ref = (string) ($vendorRef['value'] ?? Str::random(8));

        $vendor = Vendor::firstOrCreate(
            ['name' => $name],
            ['email' => Str::slug($name).'.'.$ref.'@qbo.imported'],
        );

        return (int) $vendor->getKey();
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
