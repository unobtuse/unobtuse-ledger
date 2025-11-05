<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Budget Model
 * 
 * Represents a monthly budget with income, expenses, and spending tracking.
 * Automatically calculated from transactions and bills.
 */
class Budget extends Model
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
        'month',
        'total_income',
        'expected_income',
        'bills_total',
        'bills_paid',
        'bills_pending',
        'spending_total',
        'spending_by_category',
        'category_breakdown',
        'rent_allocation',
        'rent_paid',
        'remaining_budget',
        'available_to_spend',
        'savings_goal',
        'savings_actual',
        'spending_limit',
        'status',
        'transactions_count',
        'bills_count',
        'notes',
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
            'total_income' => 'decimal:2',
            'expected_income' => 'decimal:2',
            'bills_total' => 'decimal:2',
            'bills_paid' => 'decimal:2',
            'bills_pending' => 'decimal:2',
            'spending_total' => 'decimal:2',
            'spending_by_category' => 'decimal:2',
            'rent_allocation' => 'decimal:2',
            'rent_paid' => 'boolean',
            'remaining_budget' => 'decimal:2',
            'available_to_spend' => 'decimal:2',
            'savings_goal' => 'decimal:2',
            'savings_actual' => 'decimal:2',
            'spending_limit' => 'decimal:2',
            'category_breakdown' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the budget.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate remaining budget after bills and spending.
     *
     * @return float
     */
    public function calculateRemainingBudget(): float
    {
        $income = (float) $this->total_income;
        $bills = (float) $this->bills_total;
        $spending = (float) $this->spending_total;

        return $income - $bills - $spending;
    }

    /**
     * Calculate available to spend after essential bills.
     *
     * @return float
     */
    public function calculateAvailableToSpend(): float
    {
        $income = (float) $this->total_income;
        $billsPending = (float) $this->bills_pending;
        $rentAllocation = (float) $this->rent_allocation;

        return $income - $billsPending - $rentAllocation;
    }

    /**
     * Check if budget is overspent.
     *
     * @return bool
     */
    public function isOverspent(): bool
    {
        return $this->remaining_budget < 0;
    }

    /**
     * Get spending percentage.
     *
     * @return float
     */
    public function getSpendingPercentageAttribute(): float
    {
        if (!$this->total_income || $this->total_income == 0) {
            return 0;
        }

        return ($this->spending_total / $this->total_income) * 100;
    }

    /**
     * Get bills payment progress percentage.
     *
     * @return float
     */
    public function getBillsProgressAttribute(): float
    {
        if (!$this->bills_total || $this->bills_total == 0) {
            return 0;
        }

        return ($this->bills_paid / $this->bills_total) * 100;
    }

    /**
     * Get savings progress percentage.
     *
     * @return float
     */
    public function getSavingsProgressAttribute(): float
    {
        if (!$this->savings_goal || $this->savings_goal == 0) {
            return 0;
        }

        return ($this->savings_actual / $this->savings_goal) * 100;
    }

    /**
     * Get formatted remaining budget.
     *
     * @return string
     */
    public function getFormattedRemainingBudgetAttribute(): string
    {
        $prefix = $this->remaining_budget < 0 ? '-' : '';
        return $prefix . 'USD ' . number_format(abs((float) $this->remaining_budget), 2);
    }

    /**
     * Scope to current month's budget.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentMonth($query)
    {
        return $query->where('month', now()->format('Y-m'));
    }

    /**
     * Scope to active budgets.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
