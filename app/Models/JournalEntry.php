<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entry_number',
        'entry_date',
        'reference_number',
        'memo',
        'entry_type',
        'is_approved',
        'approved_by',
        'approved_at',
        'is_posted',
        'posted_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'is_approved' => 'boolean',
        'is_posted' => 'boolean',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($journalEntry) {
            if (!$journalEntry->entry_number) {
                $journalEntry->entry_number = static::generateEntryNumber();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function getTotalDebitsAttribute()
    {
        return $this->lines()->sum('debit_amount');
    }

    public function getTotalCreditsAttribute()
    {
        return $this->lines()->sum('credit_amount');
    }

    public function isBalanced()
    {
        return bccomp($this->total_debits, $this->total_credits, 2) === 0;
    }

    public function post()
    {
        if ($this->is_posted) {
            throw new \Exception('Journal entry is already posted.');
        }

        if (!$this->isBalanced()) {
            throw new \Exception('Journal entry must be balanced before posting.');
        }

        \DB::transaction(function () {
            foreach ($this->lines as $line) {
                $account = $line->account;
                
                if ($account->normal_balance === 'debit') {
                    $account->balance += $line->debit_amount - $line->credit_amount;
                } else {
                    $account->balance += $line->credit_amount - $line->debit_amount;
                }
                
                $account->save();
            }

            $this->is_posted = true;
            $this->posted_at = now();
            $this->save();
        });

        return $this;
    }

    public function reverse()
    {
        if (!$this->is_posted) {
            throw new \Exception('Cannot reverse an unposted journal entry.');
        }

        \DB::transaction(function () {
            foreach ($this->lines as $line) {
                $account = $line->account;
                
                if ($account->normal_balance === 'debit') {
                    $account->balance -= $line->debit_amount - $line->credit_amount;
                } else {
                    $account->balance -= $line->credit_amount - $line->debit_amount;
                }
                
                $account->save();
            }

            $this->is_posted = false;
            $this->posted_at = null;
            $this->save();
        });

        return $this;
    }

    protected static function generateEntryNumber()
    {
        $year = date('Y');
        $lastEntry = static::where('entry_number', 'like', "JE-{$year}-%")
            ->orderBy('entry_number', 'desc')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) substr($lastEntry->entry_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('JE-%s-%06d', $year, $newNumber);
    }
}
