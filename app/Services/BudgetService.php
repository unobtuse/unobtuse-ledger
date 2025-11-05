<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Budget Service
 * 
 * Handles budget calculations including income, expenses, bills, and
 * remaining budget calculations.
 */
class BudgetService
{
    /**
     * Get or create budget for a specific month.
     *
     * @param User $user
     * @param string|null $month Format: YYYY-MM
     * @return Budget
     */
    public function getOrCreateBudget(User $user, ?string $month = null): Budget
    {
        $month = $month ?? now()->format('Y-m');

        return Budget::firstOrCreate(
            [
                'user_id' => $user->id,
                'month' => $month,
            ],
            [
                'status' => 'active',
            ]
        );
    }

    /**
     * Calculate and update budget for a user.
     *
     * @param User $user
     * @param string|null $month
     * @return Budget
     */
    public function calculateBudget(User $user, ?string $month = null): Budget
    {
        $budget = $this->getOrCreateBudget($user, $month);
        $month = $budget->month;

        // Calculate income from transactions
        $totalIncome = $this->calculateIncome($user, $month);

        // Calculate bills
        [$billsTotal, $billsPaid, $billsPending] = $this->calculateBills($user, $month);

        // Calculate spending
        [$spendingTotal, $categoryBreakdown] = $this->calculateSpending($user, $month);

        // Get rent allocation from pay schedule
        $rentAllocation = $this->calculateRentAllocation($user);

        // Calculate remaining budget
        $remainingBudget = $totalIncome - $billsTotal - $spendingTotal;

        // Calculate available to spend (after pending bills and rent)
        $availableToSpend = $totalIncome - $billsPending - $rentAllocation;

        // Calculate savings
        $savingsActual = $this->calculateSavings($user, $month);

        // Get transaction and bill counts
        $transactionsCount = $user->transactions()
            ->whereYear('transaction_date', '=', substr($month, 0, 4))
            ->whereMonth('transaction_date', '=', substr($month, 5, 2))
            ->count();

        $billsCount = $user->bills()
            ->whereYear('next_due_date', '=', substr($month, 0, 4))
            ->whereMonth('next_due_date', '=', substr($month, 5, 2))
            ->count();

        // Determine status
        $status = $this->determineBudgetStatus($remainingBudget, $totalIncome);

        // Update budget
        $budget->update([
            'total_income' => $totalIncome,
            'bills_total' => $billsTotal,
            'bills_paid' => $billsPaid,
            'bills_pending' => $billsPending,
            'spending_total' => $spendingTotal,
            'category_breakdown' => $categoryBreakdown,
            'rent_allocation' => $rentAllocation,
            'remaining_budget' => $remainingBudget,
            'available_to_spend' => $availableToSpend,
            'savings_actual' => $savingsActual,
            'transactions_count' => $transactionsCount,
            'bills_count' => $billsCount,
            'status' => $status,
        ]);

        return $budget->fresh();
    }

    /**
     * Calculate total income for a month.
     *
     * @param User $user
     * @param string $month
     * @return float
     */
    protected function calculateIncome(User $user, string $month): float
    {
        return (float) $user->transactions()
            ->where('transaction_type', 'credit')
            ->whereYear('transaction_date', '=', substr($month, 0, 4))
            ->whereMonth('transaction_date', '=', substr($month, 5, 2))
            ->sum(DB::raw('ABS(amount)'));
    }

    /**
     * Calculate bills for a month.
     *
     * @param User $user
     * @param string $month
     * @return array [total, paid, pending]
     */
    protected function calculateBills(User $user, string $month): array
    {
        $bills = $user->bills()
            ->whereYear('next_due_date', '=', substr($month, 0, 4))
            ->whereMonth('next_due_date', '=', substr($month, 5, 2))
            ->get();

        $total = $bills->sum('amount');
        $paid = $bills->where('payment_status', 'paid')->sum('amount');
        $pending = $bills->whereIn('payment_status', ['upcoming', 'due', 'overdue'])->sum('amount');

        return [(float) $total, (float) $paid, (float) $pending];
    }

    /**
     * Calculate spending by category for a month.
     *
     * @param User $user
     * @param string $month
     * @return array [total, breakdown]
     */
    protected function calculateSpending(User $user, string $month): array
    {
        $transactions = $user->transactions()
            ->where('transaction_type', 'debit')
            ->whereYear('transaction_date', '=', substr($month, 0, 4))
            ->whereMonth('transaction_date', '=', substr($month, 5, 2))
            ->get();

        $total = $transactions->sum('amount');

        $breakdown = $transactions->groupBy(function ($transaction) {
            return $transaction->user_category ?? $transaction->category ?? 'uncategorized';
        })->map(function ($group) {
            return $group->sum('amount');
        })->toArray();

        return [(float) $total, $breakdown];
    }

    /**
     * Calculate rent allocation (25% of net pay).
     *
     * @param User $user
     * @return float
     */
    protected function calculateRentAllocation(User $user): float
    {
        $paySchedule = $user->activePaySchedule;

        if (!$paySchedule || !$paySchedule->net_pay) {
            return 0;
        }

        return (float) $paySchedule->net_pay * 0.25;
    }

    /**
     * Calculate actual savings for a month.
     *
     * @param User $user
     * @param string $month
     * @return float
     */
    protected function calculateSavings(User $user, string $month): float
    {
        $income = $this->calculateIncome($user, $month);
        [$billsTotal] = $this->calculateBills($user, $month);
        [$spendingTotal] = $this->calculateSpending($user, $month);

        $savings = $income - $billsTotal - $spendingTotal;

        return max(0, $savings);
    }

    /**
     * Determine budget status based on calculations.
     *
     * @param float $remainingBudget
     * @param float $totalIncome
     * @return string
     */
    protected function determineBudgetStatus(float $remainingBudget, float $totalIncome): string
    {
        if ($remainingBudget < 0) {
            return 'overspent';
        }

        if ($totalIncome == 0) {
            return 'draft';
        }

        if (now()->format('Y-m') == now()->format('Y-m')) {
            return 'active';
        }

        return 'completed';
    }

    /**
     * Get budget summary for dashboard.
     *
     * @param User $user
     * @return array
     */
    public function getBudgetSummary(User $user): array
    {
        $currentBudget = $this->calculateBudget($user);
        $paySchedule = $user->activePaySchedule;

        return [
            'month' => $currentBudget->month,
            'total_income' => $currentBudget->total_income,
            'bills_total' => $currentBudget->bills_total,
            'bills_paid' => $currentBudget->bills_paid,
            'bills_pending' => $currentBudget->bills_pending,
            'spending_total' => $currentBudget->spending_total,
            'remaining_budget' => $currentBudget->remaining_budget,
            'available_to_spend' => $currentBudget->available_to_spend,
            'rent_allocation' => $currentBudget->rent_allocation,
            'savings_actual' => $currentBudget->savings_actual,
            'status' => $currentBudget->status,
            'next_pay_date' => $paySchedule?->next_pay_date,
            'days_until_payday' => $paySchedule?->days_until_payday,
        ];
    }
}

