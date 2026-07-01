<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Approvable;
use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class JournalEntry extends Model
{
    use Approvable;
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'entry_number',
        'entry_date',
        'reference_number',
        'memo',
        'entry_type',
        'approval_status',
        'rejection_reason',
    ];

    #[\Override]
    protected $casts = [
        'entry_date' => 'date',
        'is_approved' => 'boolean',
        'is_posted' => 'boolean',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::creating(function (JournalEntry $journalEntry): void {
            if (empty($journalEntry->user_id) && auth()->check()) {
                $journalEntry->user_id = auth()->id();
            }

            // ponytail: stamp tenant team on non-Filament creates (Filament stamps panel creates itself); additive, leaves DB default when tenantless.
            if (empty($journalEntry->team_id) && ($team = auth()->user()?->currentTeam) !== null) {
                $journalEntry->team_id = $team->getKey();
            }

            if (! $journalEntry->entry_number) {
                $journalEntry->entry_number = static::generateEntryNumber();
            }
        });

        // Period close: block any save (create/edit/post/reverse all route through save())
        // touching a date before the owning team's lock. No lock set = no-op, existing flows untouched.
        // ponytail: per-team lock; pure line edits via $entry->lines()->create() don't touch the parent
        // so they skip this guard — acceptable because posted entries are already immutable. Upgrade path:
        // add a JournalEntryLine saving() guard if line-level backdating ever becomes possible.
        static::saving(function (JournalEntry $journalEntry): void {
            // currentTeam() is a loose Jetstream relation (bare Model), so narrow to Team.
            // books_locked_before is a nullable date; normalise to Carbon so the compare
            // and format are type-safe. entry_date is a non-null date cast.
            $team = $journalEntry->team ?? auth()->user()?->currentTeam;
            $lock = $team instanceof Team && $team->books_locked_before
                ? Carbon::parse($team->books_locked_before)
                : null;
            $entryDate = $journalEntry->entry_date;

            if ($lock && $entryDate->lt($lock)) {
                throw new \DomainException(sprintf(
                    'Cannot save journal entry dated %s: the books are closed before %s.',
                    $entryDate->toDateString(),
                    $lock->toDateString(),
                ));
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

    public function approvalAmount(): float
    {
        return (float) $this->lines()->sum('debit_amount');
    }

    public function getTotalDebitsAttribute()
    {
        return $this->lines()->sum('debit_amount');
    }

    public function getTotalCreditsAttribute()
    {
        return $this->lines()->sum('credit_amount');
    }

    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debits, (string) $this->total_credits, 2) === 0;
    }

    public function post(): static
    {
        if ($this->is_posted) {
            throw new \Exception('Journal entry is already posted.');
        }

        if (! $this->isBalanced()) {
            throw new \Exception('Journal entry must be balanced before posting.');
        }

        \DB::transaction(function (): void {
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

    public function reverse(): static
    {
        if (! $this->is_posted) {
            throw new \Exception('Cannot reverse an unposted journal entry.');
        }

        \DB::transaction(function (): void {
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

    protected static function generateEntryNumber(): string
    {
        $year = date('Y');
        $lastEntry = static::where('entry_number', 'like', "JE-{$year}-%")
            ->orderBy('entry_number', 'desc')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) substr((string) $lastEntry->entry_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('JE-%s-%06d', $year, $newNumber);
    }
}
