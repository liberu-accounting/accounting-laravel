<?php

declare(strict_types=1);
namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        $this->logActivity($transaction, 'created');
    }

    public function updated(Transaction $transaction): void
    {
        $this->logActivity($transaction, 'updated');
    }

    public function deleted(Transaction $transaction): void
    {
        $this->logActivity($transaction, 'deleted');
    }

    private function logActivity(Transaction $transaction, string $event): void
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
