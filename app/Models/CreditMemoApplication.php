<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditMemoApplication extends Model
{
    use HasFactory;

    #[\Override]
    protected $primaryKey = 'application_id';

    #[\Override]
    protected $fillable = [
        'credit_memo_id',
        'invoice_id',
        'amount_applied',
        'application_date',
        'notes',
    ];

    #[\Override]
    protected $casts = [
        'amount_applied' => 'decimal:2',
        'application_date' => 'date',
    ];

    // Relationships
    public function creditMemo()
    {
        return $this->belongsTo(CreditMemo::class, 'credit_memo_id', 'credit_memo_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Business Logic
    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::created(function ($application): void {
            // Update credit memo's amount applied
            if ($application->creditMemo) {
                $creditMemo = $application->creditMemo;
                $creditMemo->amount_applied = $creditMemo->applications()->sum('amount_applied');

                if ($creditMemo->amount_applied >= $creditMemo->total_amount) {
                    $creditMemo->status = 'applied';
                }

                $creditMemo->save();
            }
        });

        static::deleted(function ($application): void {
            // Recalculate credit memo's amount applied
            if ($application->creditMemo) {
                $creditMemo = $application->creditMemo;
                $creditMemo->amount_applied = $creditMemo->applications()->sum('amount_applied');

                $creditMemo->status = $creditMemo->amount_applied >= $creditMemo->total_amount ? 'applied' : 'open';

                $creditMemo->save();
            }
        });
    }
}
