<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Services\AccountCsvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCsvServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AccountCsvService
    {
        return app(AccountCsvService::class);
    }

    public function test_export_includes_header_and_account_rows(): void
    {
        Account::factory()->create([
            'account_number' => 5001,
            'account_name' => 'Cash',
            'account_type' => 'asset',
        ]);

        $csv = $this->service()->export();

        $this->assertStringContainsString('account_number,account_name,account_type', $csv);
        $this->assertStringContainsString('5001', $csv);
        $this->assertStringContainsString('Cash', $csv);
        $this->assertStringContainsString('asset', $csv);
    }

    public function test_import_creates_accounts(): void
    {
        $csv = "account_number,account_name,account_type,normal_balance,opening_balance,parent_number,description,is_active\n"
            ."6001,Business Bank,asset,debit,1000,,Main account,1\n";

        $result = $this->service()->import($csv);

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseHas('accounts', [
            'account_number' => 6001,
            'account_name' => 'Business Bank',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
        ]);
    }

    public function test_import_resolves_parent_hierarchy(): void
    {
        $csv = "account_number,account_name,account_type,normal_balance,opening_balance,parent_number,description,is_active\n"
            ."6000,Assets,asset,debit,0,,Parent,1\n"
            ."6001,Bank,asset,debit,0,6000,Child,1\n";

        $this->service()->import($csv);

        $parent = Account::where('account_number', 6000)->first();
        $child = Account::where('account_number', 6001)->first();

        $this->assertNotNull($child->parent_id);
        $this->assertSame($parent->id, $child->parent_id);
    }

    public function test_import_rejects_invalid_account_type(): void
    {
        $csv = "account_number,account_name,account_type,normal_balance,opening_balance,parent_number,description,is_active\n"
            ."7001,Bad,banana,debit,0,,,1\n";

        $result = $this->service()->import($csv);

        $this->assertSame(0, $result['created']);
        $this->assertNotEmpty($result['errors']);
        $this->assertDatabaseMissing('accounts', ['account_number' => 7001]);
    }

    public function test_round_trip_preserves_hierarchy_and_types(): void
    {
        $parent = Account::factory()->create(['account_number' => 8000, 'account_type' => 'asset']);
        Account::factory()->create([
            'account_number' => 8001,
            'account_type' => 'asset',
            'parent_id' => $parent->id,
        ]);

        $csv = $this->service()->export();
        Account::query()->delete();
        $this->assertSame(0, Account::count());

        $this->service()->import($csv);

        $this->assertSame(2, Account::count());
        $reParent = Account::where('account_number', 8000)->first();
        $reChild = Account::where('account_number', 8001)->first();
        $this->assertSame($reParent->id, $reChild->parent_id);
    }
}
