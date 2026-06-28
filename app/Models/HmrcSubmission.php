<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcSubmission extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'company_id',
        'submission_type',
        'tax_period_from',
        'tax_period_to',
        'status',
        'hmrc_reference',
        'submission_data',
        'response_data',
        'error_message',
        'submitted_at',
        'accepted_at',
    ];

    #[\Override]
    protected $casts = [
        'submission_data' => 'array',
        'response_data' => 'array',
        'tax_period_from' => 'date',
        'tax_period_to' => 'date',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the company that owns the submission.
     */
    public function company(): BelongsTo
    {
        // Company's primary key is company_id; without explicit keys Laravel
        // derives company_company_id and the relation is always null.
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    /**
     * Get the VAT return for this submission.
     */
    public function vatReturn()
    {
        return $this->hasOne(HmrcVatReturn::class);
    }

    /**
     * Get the PAYE submission for this submission.
     */
    public function payeSubmission()
    {
        return $this->hasOne(HmrcPayeSubmission::class);
    }

    /**
     * Get the corporation tax submission for this submission.
     */
    public function corporationTaxSubmission()
    {
        return $this->hasOne(HmrcCorporationTaxSubmission::class);
    }

    /**
     * Mark submission as submitted.
     */
    public function markAsSubmitted(string $reference, array $responseData = []): void
    {
        $this->update([
            'status' => 'submitted',
            'hmrc_reference' => $reference,
            'response_data' => $responseData,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Mark submission as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Mark submission as rejected.
     */
    public function markAsRejected(string $errorMessage): void
    {
        $this->update([
            'status' => 'rejected',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Check if submission can be edited.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if submission is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if submission is submitted.
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'accepted']);
    }
}
