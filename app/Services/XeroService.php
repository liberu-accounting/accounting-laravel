<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\XeroConnection;
use App\Services\Concerns\RequestsProviderTokens;
use App\Services\Concerns\ResolvesSyncedContacts;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Two-way sync with Xero via OAuth 2.0. Shares the OAuth token request and
 * contact-resolution helpers with the other providers via the Concerns traits;
 * provider-specific endpoints/payloads stay here.
 */
class XeroService
{
    use RequestsProviderTokens;
    use ResolvesSyncedContacts;

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
        $tokens = $this->requestProviderTokens($this->cfg, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->cfg['redirect_uri'],
        ]);

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

    /** local account_type → Xero [Type, Class]. */
    private const XERO_ACCOUNT = [
        'asset' => ['CURRENT', 'ASSET'],
        'liability' => ['CURRLIAB', 'LIABILITY'],
        'equity' => ['EQUITY', 'EQUITY'],
        'revenue' => ['REVENUE', 'REVENUE'],
        'income' => ['REVENUE', 'REVENUE'],
        'expense' => ['EXPENSE', 'EXPENSE'],
    ];

    /** Xero Account Class → local account_type. */
    private const XERO_CLASS = [
        'ASSET' => 'asset',
        'LIABILITY' => 'liability',
        'EQUITY' => 'equity',
        'REVENUE' => 'revenue',
        'EXPENSE' => 'expense',
    ];

    public function pushAccount(Account $account, XeroConnection $connection): Account
    {
        [$type] = self::XERO_ACCOUNT[$account->account_type] ?? ['CURRENT', 'ASSET'];

        $body = $this->client($connection)->post('/Accounts', ['Accounts' => [[
            'Code' => (string) $account->account_number,
            'Name' => $account->account_name,
            'Type' => $type,
        ]]])->throw()->json();

        $account->update(['xero_id' => $body['Accounts'][0]['AccountID'] ?? $account->xero_id]);

        return $account;
    }

    public function pullAccounts(XeroConnection $connection): int
    {
        $rows = $this->client($connection)->get('/Accounts')->throw()->json()['Accounts'] ?? [];

        foreach ($rows as $row) {
            Account::updateOrCreate(
                ['xero_id' => $row['AccountID']],
                [
                    'account_name' => $row['Name'] ?? ('Xero Account '.$row['AccountID']),
                    'account_type' => self::XERO_CLASS[$row['Class'] ?? ''] ?? 'asset',
                    'account_number' => isset($row['Code']) && is_numeric($row['Code'])
                        ? (int) $row['Code']
                        : 9000 + crc32((string) $row['AccountID']) % 1000,
                ],
            );
        }

        $connection->update(['last_synced_at' => now()]);

        return count($rows);
    }

    public function pushBill(Bill $bill, XeroConnection $connection): Bill
    {
        $body = $this->client($connection)->post('/Invoices', ['Invoices' => [[
            'Type' => 'ACCPAY',
            'Contact' => ['Name' => $bill->vendor?->name ?? ('Vendor '.$bill->vendor_id)],
            'LineItems' => [[
                'Description' => 'Bill '.($bill->bill_number ?? $bill->getKey()),
                'Quantity' => 1,
                'UnitAmount' => (float) $bill->total_amount,
                'AccountCode' => '400',
            ]],
            'Status' => 'AUTHORISED',
        ]]])->throw()->json();

        $bill->update(['xero_id' => $body['Invoices'][0]['InvoiceID'] ?? $bill->xero_id]);

        return $bill;
    }

    public function pullBills(XeroConnection $connection): int
    {
        $rows = $this->client($connection)
            ->get('/Invoices', ['where' => 'Type=="ACCPAY"'])
            ->throw()
            ->json()['Invoices'] ?? [];

        foreach ($rows as $row) {
            Bill::updateOrCreate(
                ['xero_id' => $row['InvoiceID']],
                [
                    'vendor_id' => $this->resolveVendorId($row['Contact'] ?? null),
                    'total_amount' => $row['Total'] ?? 0,
                    'bill_date' => $row['Date'] ?? now()->toDateString(),
                    'due_date' => $row['DueDate'] ?? ($row['Date'] ?? now()->toDateString()),
                ],
            );
        }

        $connection->update(['last_synced_at' => now()]);

        return count($rows);
    }

    public function pushPayment(Payment $payment, XeroConnection $connection): Payment
    {
        $invoice = Invoice::find($payment->invoice_id);

        $body = $this->client($connection)->post('/Payments', ['Payments' => [[
            'Invoice' => ['InvoiceID' => $invoice?->xero_id],
            'Account' => ['Code' => '090'],
            'Amount' => (float) $payment->payment_amount,
            'Date' => optional($payment->payment_date)->format('Y-m-d') ?? (string) $payment->payment_date,
        ]]])->throw()->json();

        $payment->update(['xero_id' => $body['Payments'][0]['PaymentID'] ?? $payment->xero_id]);

        return $payment;
    }

    public function pullPayments(XeroConnection $connection): int
    {
        $rows = $this->client($connection)->get('/Payments')->throw()->json()['Payments'] ?? [];

        foreach ($rows as $row) {
            $invoiceId = (int) (Invoice::where('xero_id', $row['Invoice']['InvoiceID'] ?? null)->value('id') ?? 0);

            Payment::updateOrCreate(
                ['xero_id' => $row['PaymentID']],
                [
                    'invoice_id' => $invoiceId,
                    'payment_amount' => $row['Amount'] ?? 0,
                    'payment_date' => $row['Date'] ?? now()->toDateString(),
                ],
            );
        }

        $connection->update(['last_synced_at' => now()]);

        return count($rows);
    }

    /**
     * @param  array<string, mixed>|null  $contact
     */
    private function resolveVendorId(?array $contact): int
    {
        return $this->syncedVendorId($contact['Name'] ?? 'Xero Vendor', 'xero');
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
        return $this->syncedCustomerId($contact['Name'] ?? 'Xero Customer', 'xero');
    }
}
