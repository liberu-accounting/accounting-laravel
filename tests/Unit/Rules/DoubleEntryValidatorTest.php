<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\DoubleEntryValidator;
use Tests\TestCase;

class DoubleEntryValidatorTest extends TestCase
{
    public function test_balanced_lines_pass(): void
    {
        $lines = [
            ['debit_amount' => 100.00, 'credit_amount' => 0],
            ['debit_amount' => 0, 'credit_amount' => 100.00],
        ];

        $validator = new DoubleEntryValidator($lines);
        $this->assertTrue($validator->passes('lines', $lines));
    }

    public function test_unbalanced_lines_fail(): void
    {
        $lines = [
            ['debit_amount' => 100.00, 'credit_amount' => 0],
            ['debit_amount' => 0, 'credit_amount' => 50.00],
        ];

        $validator = new DoubleEntryValidator($lines);
        $this->assertFalse($validator->passes('lines', $lines));
    }

    public function test_zero_lines_pass(): void
    {
        $validator = new DoubleEntryValidator([]);
        $this->assertTrue($validator->passes('lines', []));
    }

    public function test_multiple_balanced_lines_pass(): void
    {
        $lines = [
            ['debit_amount' => 500.00, 'credit_amount' => 0],
            ['debit_amount' => 250.00, 'credit_amount' => 0],
            ['debit_amount' => 0, 'credit_amount' => 750.00],
        ];

        $validator = new DoubleEntryValidator($lines);
        $this->assertTrue($validator->passes('lines', $lines));
    }

    public function test_fractional_amounts_balanced(): void
    {
        $lines = [
            ['debit_amount' => 33.33, 'credit_amount' => 0],
            ['debit_amount' => 33.33, 'credit_amount' => 0],
            ['debit_amount' => 33.34, 'credit_amount' => 0],
            ['debit_amount' => 0, 'credit_amount' => 100.00],
        ];

        $validator = new DoubleEntryValidator($lines);
        $this->assertTrue($validator->passes('lines', $lines));
    }

    public function test_message_is_non_empty_string(): void
    {
        $validator = new DoubleEntryValidator([]);
        $this->assertNotEmpty($validator->message());
    }
}
