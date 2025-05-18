<?php
namespace App\Observers;

use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class TransactionObserver
{
    public function created(Transaction $transaction)
    {
        $this->logActivity($transaction, 'created');
    }

    public function updated(Transaction $transaction)
    {
        $this->logActivity($transaction, 'updated');
    }

    public function deleted(Transaction $transaction)
    {
        $this->logActivity($transaction, 'deleted');
    }

    private function logActivity(Transaction $transaction, string $event)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->transaction_id,
            'old_values' => $event !== 'created' ? $transaction->getOriginal() : null,
            'new_values' => $event !== 'deleted' ? $transaction->getAttributes() : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}