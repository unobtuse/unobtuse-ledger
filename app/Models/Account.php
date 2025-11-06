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
 * Account Model
 * 
 * Represents a linked bank account via Plaid integration.
 * Supports checking, savings, credit cards, investments, and loans.
 */
class Account extends Model
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
        'plaid_account_id',
        'plaid_access_token',
        'plaid_item_id',
        'account_name',
        'nickname',
        'official_name',
        'account_type',
        'account_subtype',
        'institution_id',
        'institution_name',
        'balance',
        'available_balance',
        'credit_limit',
        'currency',
        'mask',
        'account_number',
        'routing_number',
        'sync_status',
        'last_synced_at',
        'sync_error',
        'is_active',
        'metadata',
        'payment_due_date',
        'payment_due_date_source',
        'payment_due_day',
        'minimum_payment_amount',
        'next_payment_amount',
        'interest_rate',
        'interest_rate_type',
        'interest_rate_source',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'plaid_access_token',
        'account_number',
        'routing_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'plaid_access_token' => 'encrypted',
            'payment_due_date' => 'date',
            'minimum_payment_amount' => 'decimal:2',
            'next_payment_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for the account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the bills associated with this account.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Check if account is a credit card.
     *
     * @return bool
     */
    public function isCreditCard(): bool
    {
        return $this->account_type === 'credit_card';
    }

    /**
     * Check if account needs synchronization.
     *
     * @return bool
     */
    public function needsSync(): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }

        $syncInterval = config('plaid.sync.sync_interval', 6);
        return $this->last_synced_at->addHours($syncInterval)->isPast();
    }

    /**
     * Get formatted account display name.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->nickname ?? $this->account_name;
        return $name . ($this->mask ? ' (••••' . $this->mask . ')' : '');
    }

    /**
     * Get display name without mask.
     *
     * @return string
     */
    public function getDisplayNameWithoutMaskAttribute(): string
    {
        return $this->nickname ?? $this->account_name;
    }

    /**
     * Get status badge color class.
     *
     * @return string
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->sync_status) {
            'synced' => 'bg-chart-2/20 text-chart-2',
            'syncing' => 'bg-chart-4/20 text-chart-4',
            'failed' => 'bg-destructive/20 text-destructive',
            default => 'bg-muted text-muted-foreground',
        };
    }

    /**
     * Get account type icon SVG path.
     *
     * @return string
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->account_type) {
            'checking' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'savings' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'credit_card' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'investment' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'loan' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            default => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        };
    }

    /**
     * Get formatted balance.
     *
     * @return string
     */
    public function getFormattedBalanceAttribute(): string
    {
        return $this->currency . ' ' . number_format((float) $this->balance, 2);
    }

    /**
     * Scope to only active accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only credit card accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreditCards($query)
    {
        return $query->where('account_type', 'credit_card');
    }

    /**
     * Check if account has a payment due date.
     *
     * @return bool
     */
    public function hasPaymentDue(): bool
    {
        return $this->payment_due_date !== null;
    }

    /**
     * Calculate days until due date (negative if overdue).
     *
     * @return int|null
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->payment_due_date) {
            return null;
        }

        return now()->diffInDays($this->payment_due_date, false);
    }

    /**
     * Get due date status: 'overdue', 'warning', 'normal', or null.
     *
     * @return string|null
     */
    public function getDueDateStatusAttribute(): ?string
    {
        if (!$this->payment_due_date) {
            return null;
        }

        $daysUntilDue = $this->days_until_due;

        if ($daysUntilDue < 0) {
            return 'overdue';
        }

        if ($daysUntilDue <= 7) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Get formatted due date with days remaining.
     *
     * @return string|null
     */
    public function getFormattedDueDateAttribute(): ?string
    {
        if (!$this->payment_due_date) {
            return null;
        }

        $daysUntilDue = $this->days_until_due;
        $formattedDate = $this->payment_due_date->format('M d, Y');

        if ($daysUntilDue < 0) {
            return "Due {$formattedDate} (" . abs($daysUntilDue) . " days overdue)";
        }

        if ($daysUntilDue === 0) {
            return "Due today ({$formattedDate})";
        }

        if ($daysUntilDue === 1) {
            return "Due tomorrow ({$formattedDate})";
        }

        return "Due {$formattedDate} ({$daysUntilDue} days)";
    }

    /**
     * Get Tailwind CSS classes for due date color coding.
     *
     * @return string
     */
    public function getDueDateColorClassAttribute(): string
    {
        $status = $this->due_date_status;

        return match($status) {
            'overdue' => 'bg-destructive/20 text-destructive',
            'warning' => 'bg-chart-4/20 text-chart-4',
            'normal' => 'bg-muted text-muted-foreground',
            default => 'bg-muted text-muted-foreground',
        };
    }

    /**
     * Get formatted interest rate.
     *
     * @return string|null
     */
    public function getFormattedInterestRateAttribute(): ?string
    {
        if (!$this->interest_rate) {
            return null;
        }

        $type = $this->interest_rate_type ? " ({$this->interest_rate_type})" : '';
        return number_format((float) $this->interest_rate, 2) . '%' . $type;
    }
}
