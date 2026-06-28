<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * CSV import/export of the chart of accounts.
 *
 * ponytail: native fputcsv/fgetcsv — no maatwebsite/excel or league/csv dependency
 * for a flat account list. Add a real spreadsheet lib only if .xlsx is required.
 */
class AccountCsvService
{
    /** @var list<string> */
    private const HEADER = [
        'account_number', 'account_name', 'account_type', 'normal_balance',
        'opening_balance', 'parent_number', 'description', 'is_active',
    ];

    /** @var list<string> */
    private const TYPES = ['asset', 'liability', 'equity', 'revenue', 'income', 'expense'];

    /**
     * @param  Collection<int, Account>|null  $accounts
     */
    public function export(?Collection $accounts = null): string
    {
        $accounts ??= Account::with('parent')->orderBy('account_number')->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADER);

        foreach ($accounts as $account) {
            fputcsv($handle, [
                $account->account_number,
                $account->account_name,
                $account->account_type,
                $account->normal_balance,
                $account->opening_balance,
                $account->parent?->account_number,
                $account->description,
                $account->is_active ? 1 : 0,
            ]);
        }

        rewind($handle);

        return (string) stream_get_contents($handle);
    }

    /**
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function import(string $csv): array
    {
        $rows = $this->parse($csv);
        $created = 0;
        $updated = 0;
        $errors = [];
        $parentLinks = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2; // +1 for header, +1 for 1-based
            $number = (int) ($row['account_number'] ?? 0);
            $type = strtolower(trim((string) ($row['account_type'] ?? '')));

            if ($number === 0) {
                $errors[] = "Row {$line}: missing account_number";

                continue;
            }

            if (! in_array($type, self::TYPES, true)) {
                $errors[] = "Row {$line}: invalid account_type '{$type}'";

                continue;
            }

            $normalBalance = strtolower(trim((string) ($row['normal_balance'] ?? '')));
            if ($normalBalance !== '' && ! in_array($normalBalance, ['debit', 'credit'], true)) {
                $errors[] = "Row {$line}: invalid normal_balance '{$normalBalance}'";

                continue;
            }
            if ($normalBalance === '') {
                $normalBalance = in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
            }

            $account = Account::updateOrCreate(
                ['account_number' => $number],
                [
                    'account_name' => trim((string) ($row['account_name'] ?? '')),
                    'account_type' => $type,
                    'normal_balance' => $normalBalance,
                    'opening_balance' => $row['opening_balance'] !== null && $row['opening_balance'] !== ''
                        ? (float) $row['opening_balance']
                        : 0,
                    'description' => $row['description'] ?? null,
                    'is_active' => isset($row['is_active']) && $row['is_active'] !== ''
                        ? (bool) (int) $row['is_active']
                        : true,
                ],
            );

            $account->wasRecentlyCreated ? $created++ : $updated++;

            if (! empty($row['parent_number'])) {
                $parentLinks[$number] = (int) $row['parent_number'];
            }
        }

        foreach ($parentLinks as $childNumber => $parentNumber) {
            $child = Account::where('account_number', $childNumber)->first();
            $parent = Account::where('account_number', $parentNumber)->first();

            if ($child && $parent) {
                $child->update(['parent_id' => $parent->id]);
            } elseif ($child) {
                $errors[] = "Account {$childNumber}: parent {$parentNumber} not found";
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parse(string $csv): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $header = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null]) {
                continue; // blank line
            }

            if ($header === null) {
                $header = array_map(fn ($h): string => trim((string) $h), $data);

                continue;
            }

            if (count(array_filter($data, fn ($v): bool => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($header, array_pad($data, count($header), null));
        }

        return $rows;
    }
}
