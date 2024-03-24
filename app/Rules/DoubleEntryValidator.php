<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;

class DoubleEntryValidator implements Rule
{
    public function passes($attribute, $value)
    {
        $debitSum = request()->input('debit_account_id') ? Transaction::where('debit_account_id', request()->input('debit_account_id'))->sum('amount') : 0;
        $creditSum = request()->input('credit_account_id') ? Transaction::where('credit_account_id', request()->input('credit_account_id'))->sum('amount') : 0;

        // Adding current transaction amount to the respective sum based on the attribute being validated
        if ($attribute === 'debit_account_id') {
            $debitSum += $value;
        } else if ($attribute === 'credit_account_id') {
            $creditSum += $value;
        }

        return $debitSum == $creditSum;
    }

    public function message()
    {
        return 'The transaction does not adhere to double-entry accounting principles.';
    }
}
