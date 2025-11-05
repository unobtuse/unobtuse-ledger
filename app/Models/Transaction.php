<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transaction Model
 * 
 * Represents a financial transaction synced from Plaid or manually entered.
 * Includes categorization, recurring detection, and location data.
 */
class Transaction extends Model
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
        'plaid_transaction_id',
        'name',
        'merchant_name',
        'amount',
        'iso_currency_code',
        'transaction_date',
        'authorized_date',
        'posted_date',
        'category',
        'plaid_categories',
        'category_id',
        'category_confidence',
        'transaction_type',
        'pending',
        'is_recurring',
        'recurring_frequency',
        'recurring_group_id',
        'location_address',
        'location_city',
        'location_region',
        'location_postal_code',
        'location_country',
        'location_lat',
        'location_lon',
        'payment_channel',
        'user_category',
        'user_notes',
        'tags',
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
            'transaction_date' => 'date',
            'authorized_date' => 'date',
            'posted_date' => 'date',
            'pending' => 'boolean',
            'is_recurring' => 'boolean',
            'location_lat' => 'decimal:7',
            'location_lon' => 'decimal:7',
            'plaid_categories' => 'array',
            'tags' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account that owns the transaction.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if transaction is a debit (money out).
     *
     * @return bool
     */
    public function isDebit(): bool
    {
        return $this->transaction_type === 'debit' || $this->amount > 0;
    }

    /**
     * Check if transaction is a credit (money in).
     *
     * @return bool
     */
    public function isCredit(): bool
    {
        return $this->transaction_type === 'credit' || $this->amount < 0;
    }

    /**
     * Get absolute amount value.
     *
     * @return float
     */
    public function getAbsoluteAmountAttribute(): float
    {
        return abs((float) $this->amount);
    }

    /**
     * Get formatted amount with currency.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isDebit() ? '-' : '+';
        return $prefix . $this->iso_currency_code . ' ' . number_format($this->absolute_amount, 2);
    }

    /**
     * Scope to only recurring transactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope to pending transactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('pending', true);
    }

    /**
     * Scope to transactions in date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope to transactions by category.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where(function ($q) use ($category) {
            $q->where('category', $category)
              ->orWhere('user_category', $category);
        });
    }
}
