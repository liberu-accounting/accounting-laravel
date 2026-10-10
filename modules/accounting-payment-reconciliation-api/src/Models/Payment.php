<?php

declare(strict_types=1);

namespace Liberu\Accounting\PaymentReconciliationApi\PaymentReconciliationApiServiceProvider\Models;

use Liberu\Accounting\PaymentReconciliationApi\PaymentReconciliationApiServiceProvider\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $team_id
 * @property string|null $xero_id
 * @property string|null $sage_id
 * @property-read Invoice|null $invoice
 * @property-read Team|null $team
 */
class Payment extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $primaryKey = 'payment_id';

    #[\Override]
    protected $fillable = [
        'invoice_id',
        'payment_date',
        'payment_amount',
        'qbo_id',
        'qbo_sync_token',
        'xero_id',
        'sage_id',
        'journal_entry_id',
        'team_id',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
