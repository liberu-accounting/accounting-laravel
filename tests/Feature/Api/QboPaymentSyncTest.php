<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\QboConnection;
use App\Models\User;
use App\Services\QuickBooksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QboPaymentSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        config()->set('services.qbo', [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'environment' => 'sandbox',
            'redirect_uri' => 'https://app.test/api/qbo/callback',
            'authorization_url' => 'https://appcenter.intuit.com/connect/oauth2',
            'token_url' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
            'api_base_url' => 'https://sandbox-quickbooks.api.intuit.com',
            'webhook_verifier_token' => 'test_verifier',
        ]);
    }

    private function connection(): QboConnection
    {
        return QboConnection::create([
            'user_id' => $this->user->id,
            'realm_id' => '4620816365',
            'access_token' => 'access-test-token',
            'refresh_token' => 'refresh-test-token',
            'token_expires_at' => now()->addHour(),
            'status' => 'active',
        ]);
    }

    private function service(): QuickBooksService
    {
        return app(QuickBooksService::class);
    }

    public function test_push_payment_stores_remote_id(): void
    {
        Http::fake(['*/v3/company/*/payment*' => Http::response(['Payment' => ['Id' => '80', 'SyncToken' => '0']], 200)]);

        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'qbo_id' => '500']);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_amount' => 150.00,
            'payment_date' => '2026-06-10',
        ]);

        $this->service()->pushPayment($payment, $this->connection());

        $this->assertSame('80', $payment->fresh()->qbo_id);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v3/company/4620816365/payment') && $r->method() === 'POST');
    }

    public function test_pull_payments_creates_local_payments(): void
    {
        $invoice = Invoice::factory()->create(['qbo_id' => '500']);

        Http::fake(['*/v3/company/*/query*' => Http::response([
            'QueryResponse' => ['Payment' => [
                [
                    'Id' => '80',
                    'TotalAmt' => 150.00,
                    'TxnDate' => '2026-06-10',
                    'Line' => [['Amount' => 150.00, 'LinkedTxn' => [['TxnId' => '500', 'TxnType' => 'Invoice']]]],
                ],
            ]],
        ], 200)]);

        $count = $this->service()->pullPayments($this->connection());

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('payments', [
            'qbo_id' => '80',
            'invoice_id' => $invoice->id,
            'payment_amount' => 150.00,
        ]);
    }
}
