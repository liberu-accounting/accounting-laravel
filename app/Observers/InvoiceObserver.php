

<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class InvoiceObserver
{
    public function created(Invoice $invoice)
    {
        $this->logActivity($invoice, 'created');
    }

    public function updated(Invoice $invoice)
    {
        $this->logActivity($invoice, 'updated');
    }

    public function deleted(Invoice $invoice)
    {
        $this->logActivity($invoice, 'deleted');
    }

    private function logActivity(Invoice $invoice, string $event)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => Invoice::class,
            'auditable_id' => $invoice->invoice_id,
            'old_values' => $event !== 'created' ? $invoice->getOriginal() : null,
            'new_values' => $event !== 'deleted' ? $invoice->getAttributes() : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}