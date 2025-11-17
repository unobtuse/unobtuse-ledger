<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Model
 * 
 * Represents a payment transaction initiated through Teller API.
 */
class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'account_id',
        'teller_payment_id',
        'recipient_name',
        'recipient_account_number',
        'recipient_routing_number',
        'payment_type',
        'payment_method',
        'amount',
        'currency',
        'status',
        'status_message',
        'scheduled_date',
        'processed_date',
        'recurrence_frequency',
        'recurrence_end_date',
        'memo',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'scheduled_date' => 'datetime',
            'processed_date' => 'datetime',
            'recurrence_end_date' => 'date',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account associated with the payment.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get formatted amount.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format((float) $this->amount, 2);
    }

    /**
     * Check if payment is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope to pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
