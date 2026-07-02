<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BankStatement;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BankStatementImportService
{
    public function importFromCsv(string $filePath, BankStatement $bankStatement): Collection
    {
        $transactions = collect();
        $handle = fopen($filePath, 'r');

        // Skip header row
        fgetcsv($handle, escape: '\\');

        while (($data = fgetcsv($handle, escape: '\\')) !== false) {
            try {
                $transaction = Transaction::create([
                    'transaction_date' => $this->parseDate($data[0]),
                    'description' => $data[1],
                    'amount' => $this->parseAmount($data[2]),
                    'account_id' => $bankStatement->account_id,
                    'bank_statement_id' => $bankStatement->id,
                    'reconciled' => false,
                ]);

                $transactions->push($transaction);
            } catch (\Exception $e) {
                Log::error('Failed to import transaction: '.$e->getMessage());
            }
        }

        fclose($handle);

        return $transactions;
    }

    public function importFromOfx(string $filePath, BankStatement $bankStatement): Collection
    {
        $transactions = collect();
        $ofx = simplexml_load_file($filePath);

        foreach ($ofx->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN as $txn) {
            try {
                $transaction = Transaction::create([
                    'transaction_date' => $this->parseDate((string) $txn->DTPOSTED),
                    'description' => (string) $txn->MEMO,
                    'amount' => $this->parseAmount((string) $txn->TRNAMT),
                    'account_id' => $bankStatement->account_id,
                    'bank_statement_id' => $bankStatement->id,
                    'reconciled' => false,
                ]);

                $transactions->push($transaction);
            } catch (\Exception $e) {
                Log::error('Failed to import OFX transaction: '.$e->getMessage());
            }
        }

        return $transactions;
    }

    public function importFromQif(string $filePath, BankStatement $bankStatement): Collection
    {
        $transactions = collect();
        $handle = fopen($filePath, 'r');

        $record = [];
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");

            // Skip blank lines and the `!Type:...` header declaration.
            if ($line === '' || str_starts_with($line, '!')) {
                continue;
            }

            // `^` terminates the current record.
            if ($line === '^') {
                if ($record !== []) {
                    $this->createQifTransaction($record, $bankStatement, $transactions);
                    $record = [];
                }

                continue;
            }

            // Each line is a single-char field code followed by its value.
            $record[$line[0]] = substr($line, 1);
        }

        // Flush a trailing record that was not `^`-terminated.
        if ($record !== []) {
            $this->createQifTransaction($record, $bankStatement, $transactions);
        }

        fclose($handle);

        return $transactions;
    }

    public function importFromCamt(string $filePath, BankStatement $bankStatement): Collection
    {
        $transactions = collect();
        $xml = simplexml_load_file($filePath);

        // camt.053 lives in a versioned namespace (…camt.053.001.xx). Query by
        // local-name() so the version's namespace URI stays out of the code.
        foreach ($xml->xpath('//*[local-name()="Ntry"]') ?: [] as $ntry) {
            try {
                $amount = $this->parseAmount((string) ($ntry->xpath('*[local-name()="Amt"]')[0] ?? '0'));
                $indicator = (string) ($ntry->xpath('*[local-name()="CdtDbtInd"]')[0] ?? '');
                // camt amounts are unsigned; CdtDbtInd carries direction (DBIT = money out).
                $amount = abs($amount) * ($indicator === 'DBIT' ? -1 : 1);

                $date = (string) ($ntry->xpath('*[local-name()="BookgDt"]/*[local-name()="Dt"]')[0] ?? '');

                $description = (string) ($ntry->xpath('.//*[local-name()="RmtInf"]/*[local-name()="Ustrd"]')[0] ?? '');
                if ($description === '') {
                    $description = (string) ($ntry->xpath('*[local-name()="AddtlNtryInf"]')[0] ?? '');
                }

                $transaction = Transaction::create([
                    'transaction_date' => $this->parseDate($date),
                    'description' => $description,
                    'amount' => $amount,
                    'account_id' => $bankStatement->account_id,
                    'bank_statement_id' => $bankStatement->id,
                    'reconciled' => false,
                ]);

                $transactions->push($transaction);
            } catch (\Exception $e) {
                Log::error('Failed to import CAMT transaction: '.$e->getMessage());
            }
        }

        return $transactions;
    }

    /**
     * @param  array<string, string>  $record  QIF field codes → values.
     */
    private function createQifTransaction(array $record, BankStatement $bankStatement, Collection $transactions): void
    {
        try {
            // Intuit QIF uses `'` (and sometimes `` ` ``) to separate day from a 20xx
            // year (e.g. 6/15'24); normalise to a Carbon-parseable form.
            $date = str_replace(["'", '`'], '/', trim($record['D'] ?? ''));

            // Payee is the primary description, memo is the fallback. (N/reference is
            // ignored to match the OFX path, which also drops its FITID.)
            $description = ($record['P'] ?? '') !== '' ? $record['P'] : ($record['M'] ?? '');

            $transaction = Transaction::create([
                'transaction_date' => $this->parseDate($date),
                'description' => $description,
                'amount' => $this->parseAmount($record['T'] ?? ($record['U'] ?? '0')),
                'account_id' => $bankStatement->account_id,
                'bank_statement_id' => $bankStatement->id,
                'reconciled' => false,
            ]);

            $transactions->push($transaction);
        } catch (\Exception $e) {
            Log::error('Failed to import QIF transaction: '.$e->getMessage());
        }
    }

    private function parseDate(string $date): Carbon
    {
        return Carbon::parse($date);
    }

    private function parseAmount(string $amount): float
    {
        return (float) str_replace(['$', ','], '', $amount);
    }
}
