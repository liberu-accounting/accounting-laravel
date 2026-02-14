<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DoubleEntryValidator implements Rule
{
    protected $lines;

    public function __construct($lines = null)
    {
        $this->lines = $lines;
    }

    public function passes($attribute, $value)
    {
        // If validating journal entry lines
        if ($this->lines !== null) {
            $totalDebits = 0;
            $totalCredits = 0;

            foreach ($this->lines as $line) {
                $debit = is_array($line) ? ($line['debit_amount'] ?? 0) : ($line->debit_amount ?? 0);
                $credit = is_array($line) ? ($line['credit_amount'] ?? 0) : ($line->credit_amount ?? 0);
                
                $totalDebits += floatval($debit);
                $totalCredits += floatval($credit);
            }

            // Use bccomp for precise decimal comparison
            return bccomp($totalDebits, $totalCredits, 2) === 0;
        }

        // Legacy validation for simple transactions (backwards compatibility)
        // This validates that a single transaction has equal debit and credit
        $debitAmount = request()->input('debit_amount', 0);
        $creditAmount = request()->input('credit_amount', 0);
        
        return bccomp($debitAmount, $creditAmount, 2) === 0;
    }

    public function message()
    {
        return 'Total debits must equal total credits. This transaction does not adhere to double-entry accounting principles.';
    }
}
