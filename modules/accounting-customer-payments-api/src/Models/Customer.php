<?php

declare(strict_types=1);

namespace Liberu\Accounting\CustomerPaymentsApi\CustomerPaymentsApiServiceProvider\Models;

use Liberu\Accounting\CustomerPaymentsApi\CustomerPaymentsApiServiceProvider\Traits\IsTenantModel;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string|null $qbo_id
 * @property string|null $sage_id
 * @property string|null $xero_id
 * @property int|null $team_id
 * @property int $customer_id
 * @property string|null $password
 */
class Customer extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;
    use IsTenantModel;

    // protected $primaryKey = 'customer_id';
    protected $guard = 'customer';

    #[\Override]
    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Customers may reach only their own portal — never the staff panels.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'customer';
    }

    #[\Override]
    protected $fillable = [
        'customer_name',
        'customer_last_name',
        'customer_address',
        'customer_email',
        'customer_phone',
        'customer_city',
        'credit_hold',
        'qbo_id',
        'sage_id',
        'xero_id',
        'team_id',
    ];

    #[\Override]
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class, 'customer_id', 'customer_id');
    }

    public function creditMemos()
    {
        return $this->hasMany(CreditMemo::class, 'customer_id', 'customer_id');
    }

    public function salesReceipts()
    {
        return $this->hasMany(SalesReceipt::class, 'customer_id', 'customer_id');
    }

    public function delayedCharges()
    {
        return $this->hasMany(DelayedCharge::class, 'customer_id', 'customer_id');
    }

    public function refundReceipts()
    {
        return $this->hasMany(RefundReceipt::class, 'customer_id', 'customer_id');
    }

    public function isOverCreditLimit(): bool
    {
        return $this->credit_limit > 0 && $this->current_balance >= $this->credit_limit;
    }

    public function updateBalance(): void
    {
        $this->current_balance = $this->invoices()
            ->where('payment_status', 'pending')
            ->sum('total_amount');
        $this->save();
    }

    public function routeNotificationForSms(mixed $notification = null): ?string
    {
        return $this->customer_phone;
    }
}
