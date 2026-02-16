<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcSubmission extends Model
{
    use HasFactory;

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

    protected $casts = [
        'submission_data' => 'array',
        'response_data' => 'array',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the company that owns the submission.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
