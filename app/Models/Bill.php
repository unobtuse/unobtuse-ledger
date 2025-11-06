<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bill Model
 * 
 * Represents a recurring bill or payment obligation.
 * Can be auto-detected from transactions or manually entered.
 */
class Bill extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'description',
        'amount',
        'currency',
        'due_date',
        'next_due_date',
        'frequency',
        'frequency_value',
        'category',
        'payment_status',
        'last_payment_date',
        'last_payment_amount',
        'is_autopay',
        'autopay_account',
        'payment_link',
        'payee_name',
        'auto_detected',
        'source_transaction_id',
        'detection_confidence',
        'reminder_enabled',
        'reminder_days_before',
        'notes',
        'priority',
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
            'due_date' => 'date',
            'next_due_date' => 'date',
            'last_payment_date' => 'date',
            'last_payment_amount' => 'decimal:2',
            'is_autopay' => 'boolean',
            'auto_detected' => 'boolean',
            'reminder_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the bill.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account associated with the bill.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all transactions that paid this bill.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if bill is overdue.
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        return $this->payment_status === 'overdue' || 
               ($this->next_due_date->isPast() && $this->payment_status !== 'paid');
    }

    /**
     * Check if bill is due soon (within reminder days).
     *
     * @return bool
     */
    public function isDueSoon(): bool
    {
        $reminderDate = now()->addDays($this->reminder_days_before);
        return $this->next_due_date->isBefore($reminderDate);
    }

    /**
     * Calculate next due date based on frequency.
     *
     * @return \Carbon\Carbon
     */
    public function calculateNextDueDate()
    {
        $currentDue = $this->next_due_date;

        return match($this->frequency) {
            'weekly' => $currentDue->addWeek(),
            'biweekly' => $currentDue->addWeeks(2),
            'monthly' => $currentDue->addMonth(),
            'quarterly' => $currentDue->addMonths(3),
            'annual' => $currentDue->addYear(),
            default => $currentDue->addDays($this->frequency_value ?? 30),
        };
    }

    /**
     * Mark bill as paid.
     *
     * @param float $amount
     * @return void
     */
    public function markAsPaid(float $amount): void
    {
        $this->update([
            'payment_status' => 'paid',
            'last_payment_date' => now(),
            'last_payment_amount' => $amount,
            'next_due_date' => $this->calculateNextDueDate(),
        ]);
    }

    /**
     * Get formatted amount with currency.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format((float) $this->amount, 2);
    }

    /**
     * Get days until due.
     *
     * @return int
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->next_due_date, false);
    }

    /**
     * Scope to upcoming bills.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('payment_status', 'upcoming')
                     ->orderBy('next_due_date', 'asc');
    }

    /**
     * Scope to overdue bills.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'overdue')
                     ->orWhere(function($q) {
                         $q->where('next_due_date', '<', now())
                           ->where('payment_status', '!=', 'paid');
                     });
    }

    /**
     * Scope to bills due before next payday.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $payDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDueBeforePayday($query, $payDate)
    {
        return $query->where('next_due_date', '<=', $payDate)
                     ->whereIn('payment_status', ['upcoming', 'due']);
    }
}
