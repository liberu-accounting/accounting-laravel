<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\Transaction;
use App\Services\BankStatementImportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class BankStatementImportServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function service(): BankStatementImportService
    {
        return new BankStatementImportService;
    }

    private function tempFile(string $contents): string
    {
        // Extension is irrelevant: fgetcsv/simplexml_load_file read by content.
        $path = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function statement(): BankStatement
    {
        $account = Account::factory()->create();

        return BankStatement::factory()->create([
            'account_id' => $account->id,
            'statement_date' => Carbon::parse('2024-06-15'),
        ]);
    }

    // Case 8: header skipped, one Transaction per remaining row, reconciled=false, Collection returned.
    public function test_import_from_csv_skips_header_and_creates_transactions(): void
    {
        $statement = $this->statement();
        $csv = "Date,Description,Amount\n"
            ."2024-06-15,Coffee,\$12.50\n"
            ."2024-06-16,Lunch,\$8.00\n";
        $path = $this->tempFile($csv);

        $result = $this->service()->importFromCsv($path, $statement);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame(2, Transaction::where('bank_statement_id', $statement->id)->count());

        $first = $result->first();
        $this->assertEquals('2024-06-15', $first->transaction_date->format('Y-m-d'));
        $this->assertEquals(12.50, (float) $first->amount);
        $this->assertSame('Coffee', $first->description);
        $this->assertSame($statement->account_id, $first->account_id);
        $this->assertSame($statement->id, $first->bank_statement_id);
        // reconciled is guarded, so it lands on the DB default (false) — assert the persisted row.
        $this->assertFalse($first->fresh()->reconciled);
    }

    // Case 9: malformed row (unparseable date) is caught, logged, skipped; other rows still import.
    public function test_import_from_csv_skips_malformed_rows(): void
    {
        $statement = $this->statement();
        $csv = "Date,Description,Amount\n"
            ."2024-06-15,Coffee,\$12.50\n"
            ."not-a-date,Broken,\$99.00\n"
            ."2024-06-17,Lunch,\$8.00\n";
        $path = $this->tempFile($csv);

        $result = $this->service()->importFromCsv($path, $statement);

        // The bad-date row throws in parseDate, is caught + logged, and skipped.
        $this->assertCount(2, $result);
        $this->assertSame(2, Transaction::where('bank_statement_id', $statement->id)->count());
        $this->assertEqualsCanonicalizing(
            ['2024-06-15', '2024-06-17'],
            $result->map(fn ($t) => $t->transaction_date->format('Y-m-d'))->all()
        );
    }

    // Case 10: parseAmount strips $ and thousands separators; handles negatives and plain ints.
    public function test_parse_amount_strips_symbols_and_handles_signs(): void
    {
        $method = new ReflectionMethod(BankStatementImportService::class, 'parseAmount');
        $svc = $this->service();

        $this->assertSame(1234.56, $method->invoke($svc, '$1,234.56'));
        $this->assertSame(-500.0, $method->invoke($svc, '-500.00'));
        $this->assertSame(-50.0, $method->invoke($svc, '-$50.00'));
        $this->assertSame(1000.0, $method->invoke($svc, '1000'));
        $this->assertSame(1000.0, $method->invoke($svc, '$1,000'));
    }

    // Case 11: parseDate handles the CSV/OFX date formats used.
    public function test_parse_date_handles_supported_formats(): void
    {
        $method = new ReflectionMethod(BankStatementImportService::class, 'parseDate');
        $svc = $this->service();

        $this->assertInstanceOf(Carbon::class, $method->invoke($svc, '2024-06-15'));
        $this->assertEquals('2024-06-15', $method->invoke($svc, '2024-06-15')->format('Y-m-d'));
        $this->assertEquals('2024-06-15', $method->invoke($svc, '20240615')->format('Y-m-d'));   // OFX DTPOSTED
        $this->assertEquals('2024-06-15', $method->invoke($svc, '06/15/2024')->format('Y-m-d')); // US CSV
    }

    // Case 12: importFromOfx parses STMTTRN nodes into transactions.
    public function test_import_from_ofx_parses_stmttrn_nodes(): void
    {
        $statement = $this->statement();
        $ofx = <<<'XML'
<?xml version="1.0"?>
<OFX>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <BANKTRANLIST>
          <STMTTRN>
            <DTPOSTED>20240615</DTPOSTED>
            <TRNAMT>150.00</TRNAMT>
            <MEMO>Coffee</MEMO>
          </STMTTRN>
          <STMTTRN>
            <DTPOSTED>20240616</DTPOSTED>
            <TRNAMT>-42.50</TRNAMT>
            <MEMO>Refund</MEMO>
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
XML;
        $path = $this->tempFile($ofx);

        $result = $this->service()->importFromOfx($path, $statement);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame(2, Transaction::where('bank_statement_id', $statement->id)->count());

        $first = $result->first();
        $this->assertEquals('2024-06-15', $first->transaction_date->format('Y-m-d'));
        $this->assertEquals(150.00, (float) $first->amount);
        $this->assertSame('Coffee', $first->description);
        $this->assertSame($statement->account_id, $first->account_id);

        $second = $result->last();
        $this->assertEquals('2024-06-16', $second->transaction_date->format('Y-m-d'));
        $this->assertEquals(-42.50, (float) $second->amount);
        $this->assertSame('Refund', $second->description);
    }

    // Case 13: importFromQif parses `^`-terminated records; T sign is preserved (credit + debit).
    public function test_import_from_qif_parses_records_with_signed_amounts(): void
    {
        $statement = $this->statement();
        $qif = "!Type:Bank\n"
            ."D06/15/2024\nT500.00\nPPaycheck\nMSalary\nN1001\n^\n"
            ."D6/16'24\nT-42.50\nPGrocery Store\n^\n";
        $path = $this->tempFile($qif);

        $result = $this->service()->importFromQif($path, $statement);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame(2, Transaction::where('bank_statement_id', $statement->id)->count());

        $credit = $result->first();
        $this->assertEquals('2024-06-15', $credit->transaction_date->format('Y-m-d'));
        $this->assertEquals(500.00, (float) $credit->amount);      // deposit stays positive
        $this->assertSame('Paycheck', $credit->description);        // payee wins over memo
        $this->assertSame($statement->account_id, $credit->account_id);
        $this->assertFalse($credit->fresh()->reconciled);

        $debit = $result->last();
        $this->assertEquals('2024-06-16', $debit->transaction_date->format('Y-m-d')); // apostrophe year normalised
        $this->assertEquals(-42.50, (float) $debit->amount);        // withdrawal stays negative
        $this->assertSame('Grocery Store', $debit->description);
    }

    // Case 14: importFromCamt reads Ntry entries; CdtDbtInd drives the sign (CRDT + / DBIT −).
    public function test_import_from_camt_parses_entries_with_credit_debit_signs(): void
    {
        $statement = $this->statement();
        $camt = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.02">
  <BkToCstmrStmt>
    <Stmt>
      <Ntry>
        <Amt Ccy="EUR">500.00</Amt>
        <CdtDbtInd>CRDT</CdtDbtInd>
        <BookgDt><Dt>2024-06-15</Dt></BookgDt>
        <NtryDtls><TxDtls><RmtInf><Ustrd>Incoming payment</Ustrd></RmtInf></TxDtls></NtryDtls>
      </Ntry>
      <Ntry>
        <Amt Ccy="EUR">42.50</Amt>
        <CdtDbtInd>DBIT</CdtDbtInd>
        <BookgDt><Dt>2024-06-16</Dt></BookgDt>
        <AddtlNtryInf>Card purchase</AddtlNtryInf>
      </Ntry>
    </Stmt>
  </BkToCstmrStmt>
</Document>
XML;
        $path = $this->tempFile($camt);

        $result = $this->service()->importFromCamt($path, $statement);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame(2, Transaction::where('bank_statement_id', $statement->id)->count());

        $credit = $result->first();
        $this->assertEquals('2024-06-15', $credit->transaction_date->format('Y-m-d'));
        $this->assertEquals(500.00, (float) $credit->amount);       // CRDT → positive
        $this->assertSame('Incoming payment', $credit->description); // RmtInf/Ustrd
        $this->assertSame($statement->account_id, $credit->account_id);
        $this->assertFalse($credit->fresh()->reconciled);

        $debit = $result->last();
        $this->assertEquals('2024-06-16', $debit->transaction_date->format('Y-m-d'));
        $this->assertEquals(-42.50, (float) $debit->amount);        // DBIT → negative
        $this->assertSame('Card purchase', $debit->description);     // AddtlNtryInf fallback
    }
}
