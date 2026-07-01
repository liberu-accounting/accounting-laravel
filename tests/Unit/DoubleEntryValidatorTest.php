<?php

namespace Tests\Unit;

use App\Rules\DoubleEntryValidator;
use Tests\TestCase;

class DoubleEntryValidatorTest extends TestCase
{
    public function test_validates_balanced_journal_entry_lines(): void
    {
        $lines = [
            ['debit_amount' => 500.00, 'credit_amount' => 0.00],
            ['debit_amount' => 0.00, 'credit_amount' => 300.00],
            ['debit_amount' => 0.00, 'credit_amount' => 200.00],
        ];

        $validator = new DoubleEntryValidator($lines);

        $this->assertTrue($validator->passes('lines', $lines));
    }

    public function test_rejects_unbalanced_journal_entry_lines(): void
    {
        $lines = [
            ['debit_amount' => 500.00, 'credit_amount' => 0.00],
            ['debit_amount' => 0.00, 'credit_amount' => 300.00],
        ];

        $validator = new DoubleEntryValidator($lines);

        $this->assertFalse($validator->passes('lines', $lines));
    }

    public function test_validates_with_decimal_precision(): void
    {
        $lines = [
            ['debit_amount' => 100.33, 'credit_amount' => 0.00],
            ['debit_amount' => 50.67, 'credit_amount' => 0.00],
            ['debit_amount' => 0.00, 'credit_amount' => 151.00],
        ];

        $validator = new DoubleEntryValidator($lines);

        $this->assertTrue($validator->passes('lines', $lines));
    }

    public function test_handles_objects_with_properties(): void
    {
        $line1 = (object) ['debit_amount' => 100.00, 'credit_amount' => 0.00];
        $line2 = (object) ['debit_amount' => 0.00, 'credit_amount' => 100.00];

        $lines = [$line1, $line2];

        $validator = new DoubleEntryValidator($lines);

        $this->assertTrue($validator->passes('lines', $lines));
    }

    public function test_validates_empty_lines_as_balanced(): void
    {
        $lines = [];

        $validator = new DoubleEntryValidator($lines);

        $this->assertTrue($validator->passes('lines', $lines));
    }

    public function test_provides_meaningful_error_message(): void
    {
        $validator = new DoubleEntryValidator([]);

        $message = $validator->message();

        $this->assertStringContainsString('Total debits must equal total credits', $message);
        $this->assertStringContainsString('double-entry accounting', $message);
    }

    public function test_legacy_branch_passes_for_balanced_request_amounts(): void
    {
        // lines === null -> legacy branch reads request() input
        request()->merge(['debit_amount' => 250.00, 'credit_amount' => 250.00]);

        $validator = new DoubleEntryValidator();

        $this->assertTrue($validator->passes('debit_amount', 250.00));
    }

    public function test_legacy_branch_fails_for_unbalanced_request_amounts(): void
    {
        request()->merge(['debit_amount' => 250.00, 'credit_amount' => 100.00]);

        $validator = new DoubleEntryValidator();

        $this->assertFalse($validator->passes('debit_amount', 250.00));
    }

    public function test_lines_missing_keys_fall_back_to_zero(): void
    {
        // Each line omits one key, exercising the `?? 0` fallback; totals stay balanced.
        $lines = [
            ['credit_amount' => 100.00], // debit_amount missing -> 0
            ['debit_amount' => 100.00],  // credit_amount missing -> 0
        ];

        $validator = new DoubleEntryValidator($lines);

        $this->assertTrue($validator->passes('lines', $lines));
    }
}
