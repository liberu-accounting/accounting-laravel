<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Rules\DoubleEntryValidator;

class DoubleEntryValidatorTest extends TestCase
{
    /** @test */
    public function it_validates_balanced_journal_entry_lines()
    {
        $lines = [
            ['debit_amount' => 500.00, 'credit_amount' => 0.00],
            ['debit_amount' => 0.00, 'credit_amount' => 300.00],
            ['debit_amount' => 0.00, 'credit_amount' => 200.00],
        ];

        $validator = new DoubleEntryValidator($lines);
        
        $this->assertTrue($validator->passes('lines', $lines));
    }

    /** @test */
    public function it_rejects_unbalanced_journal_entry_lines()
    {
        $lines = [
            ['debit_amount' => 500.00, 'credit_amount' => 0.00],
            ['debit_amount' => 0.00, 'credit_amount' => 300.00],
        ];

        $validator = new DoubleEntryValidator($lines);
        
        $this->assertFalse($validator->passes('lines', $lines));
    }

    /** @test */
    public function it_validates_with_decimal_precision()
    {
        $lines = [
            ['debit_amount' => 100.33, 'credit_amount' => 0.00],
            ['debit_amount' => 50.67, 'credit_amount' => 0.00],
            ['debit_amount' => 0.00, 'credit_amount' => 151.00],
        ];

        $validator = new DoubleEntryValidator($lines);
        
        $this->assertTrue($validator->passes('lines', $lines));
    }

    /** @test */
    public function it_handles_objects_with_properties()
    {
        $line1 = (object)['debit_amount' => 100.00, 'credit_amount' => 0.00];
        $line2 = (object)['debit_amount' => 0.00, 'credit_amount' => 100.00];
        
        $lines = [$line1, $line2];

        $validator = new DoubleEntryValidator($lines);
        
        $this->assertTrue($validator->passes('lines', $lines));
    }

    /** @test */
    public function it_validates_empty_lines_as_balanced()
    {
        $lines = [];

        $validator = new DoubleEntryValidator($lines);
        
        $this->assertTrue($validator->passes('lines', $lines));
    }

    /** @test */
    public function it_provides_meaningful_error_message()
    {
        $validator = new DoubleEntryValidator([]);
        
        $message = $validator->message();
        
        $this->assertStringContainsString('Total debits must equal total credits', $message);
        $this->assertStringContainsString('double-entry accounting', $message);
    }
}
