<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\BankStatement;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReconciliationService;
    }

    public function test_reconcile_returns_expected_keys(): void
    {
        $statement = BankStatement::factory()->create();

        $result = $this->service->reconcile($statement);

        $this->assertArrayHasKey('matched_transactions', $result);
        $this->assertArrayHasKey('unmatched_transactions', $result);
        $this->assertArrayHasKey('balance_discrepancy', $result);
        $this->assertArrayHasKey('total_credits', $result);
        $this->assertArrayHasKey('total_debits', $result);
    }

    public function test_reconcile_counts_are_non_negative(): void
    {
        $statement = BankStatement::factory()->create();

        $result = $this->service->reconcile($statement);

        $this->assertGreaterThanOrEqual(0, $result['matched_transactions']);
        $this->assertGreaterThanOrEqual(0, $result['unmatched_transactions']);
    }

    public function test_reconcile_discrepancies_is_iterable(): void
    {
        $statement = BankStatement::factory()->create();

        $result = $this->service->reconcile($statement);

        $this->assertIsIterable($result['discrepancies']);
    }
}
