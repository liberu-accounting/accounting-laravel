<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditMemoApplication extends Model
{
    use HasFactory;

    protected $primaryKey = 'application_id';

    protected $fillable = [
        'credit_memo_id',
        'invoice_id',
        'amount_applied',
        'application_date',
        'notes',
    ];

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
    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($application) {
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

        static::deleted(function ($application) {
            // Recalculate credit memo's amount applied
            if ($application->creditMemo) {
                $creditMemo = $application->creditMemo;
                $creditMemo->amount_applied = $creditMemo->applications()->sum('amount_applied');
                
                if ($creditMemo->amount_applied >= $creditMemo->total_amount) {
                    $creditMemo->status = 'applied';
                } else {
                    $creditMemo->status = 'open';
                }
                
                $creditMemo->save();
            }
        });
    }
}
