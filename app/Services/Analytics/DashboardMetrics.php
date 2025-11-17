<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Bill;
use App\Models\PaySchedule;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardMetrics
{
    protected User $user;
    protected int $cacheDuration = 900; // 15 minutes

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get net worth (total assets - liabilities)
     */
    public function getNetWorth(): array
    {
        return Cache::remember(
            "dashboard.networth.{$this->user->id}",
            $this->cacheDuration,
            function () {
                $accounts = $this->user->accounts()->where('is_active', true)->get();

                $assets = $accounts->whereIn('account_subtype', ['checking', 'savings', 'investment'])
                    ->sum('balance');

                $liabilities = $accounts->whereIn('account_subtype', ['credit_card', 'loan'])
                    ->sum('balance');

                $netWorth = $assets - abs($liabilities);

                // Calculate trend from last month
                $lastMonth = Carbon::now()->subMonth();
                $lastMonthNetWorth = $this->getNetWorthForDate($lastMonth);
                $change = $netWorth - $lastMonthNetWorth;
                $changePercent = $lastMonthNetWorth != 0
                    ? ($change / abs($lastMonthNetWorth)) * 100
                    : 0;

                return [
                    'total' => $netWorth,
                    'assets' => $assets,
                    'liabilities' => abs($liabilities),
                    'change' => $change,
                    'change_percent' => round($changePercent, 2),
                    'trend' => $change >= 0 ? 'up' : 'down',
                ];
            }
        );
    }

    /**
     * Get net worth for a specific date (simplified - just uses current balances)
     */
    protected function getNetWorthForDate(Carbon $date): float
    {
        // In a production app, you'd track balance history
        // For now, we'll estimate based on transaction deltas
        $accounts = $this->user->accounts()->where('is_active', true)->get();

        $currentNetWorth = $accounts->whereIn('account_subtype', ['checking', 'savings', 'investment'])->sum('balance')
            - abs($accounts->whereIn('account_subtype', ['credit_card', 'loan'])->sum('balance'));

        // Get transactions since the date and subtract them
        $transactionsDelta = $this->user->transactions()
            ->where('transaction_date', '>', $date)
            ->get()
            ->sum(function ($transaction) {
                return $transaction->isCredit() ? -$transaction->amount : $transaction->amount;
            });

        return $currentNetWorth - $transactionsDelta;
    }

    /**
     * Get current month cash flow
     */
    public function getCurrentMonthCashFlow(): array
    {
        return Cache::remember(
            "dashboard.cashflow.{$this->user->id}." . now()->format('Y-m'),
            $this->cacheDuration,
            function () {
                $startOfMonth = Carbon::now()->startOfMonth();
                $endOfMonth = Carbon::now()->endOfMonth();

                $transactions = $this->user->transactions()
                    ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                    ->get();

                $income = $transactions->filter(fn($t) => $t->isCredit())->sum('amount');
                $expenses = $transactions->filter(fn($t) => $t->isDebit())->sum('amount');
                $net = $income - $expenses;

                // Compare to last month
                $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
                $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

                $lastMonthTransactions = $this->user->transactions()
                    ->whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd])
                    ->get();

                $lastMonthIncome = $lastMonthTransactions->filter(fn($t) => $t->isCredit())->sum('amount');
                $lastMonthExpenses = $lastMonthTransactions->filter(fn($t) => $t->isDebit())->sum('amount');
                $lastMonthNet = $lastMonthIncome - $lastMonthExpenses;

                $netChange = $net - $lastMonthNet;
                $netChangePercent = $lastMonthNet != 0 ? ($netChange / abs($lastMonthNet)) * 100 : 0;

                return [
                    'income' => $income,
                    'expenses' => $expenses,
                    'net' => $net,
                    'last_month_net' => $lastMonthNet,
                    'change' => $netChange,
                    'change_percent' => round($netChangePercent, 2),
                    'trend' => $netChange >= 0 ? 'up' : 'down',
                ];
            }
        );
    }

    /**
     * Get available cash (checking + savings)
     */
    public function getAvailableCash(): array
    {
        $cash = $this->user->accounts()
            ->whereIn('account_subtype', ['checking', 'savings'])
            ->where('is_active', true)
            ->sum('available_balance');

        return [
            'total' => $cash,
            'formatted' => '$' . number_format($cash, 2),
        ];
    }

    /**
     * Get next payday information
     */
    public function getNextPayday(): array
    {
        $paySchedule = $this->user->paySchedules()->where('is_active', true)->first();

        if (!$paySchedule) {
            return [
                'has_schedule' => false,
                'days_until' => null,
                'date' => null,
                'amount' => 0,
            ];
        }

        $nextPayDate = $paySchedule->calculateNextPayDate();
        $daysUntil = Carbon::now()->diffInDays($nextPayDate, false);

        return [
            'has_schedule' => true,
            'days_until' => (int) $daysUntil,
            'date' => $nextPayDate,
            'formatted_date' => $nextPayDate->format('M j, Y'),
            'amount' => $paySchedule->net_pay,
            'formatted_amount' => '$' . number_format($paySchedule->net_pay, 2),
        ];
    }

    /**
     * Get spending trend data (last 12 months)
     */
    public function getSpendingTrend(int $months = 12): array
    {
        return Cache::remember(
            "dashboard.spending_trend.{$this->user->id}.{$months}",
            $this->cacheDuration,
            function () use ($months) {
                $data = [];
                $labels = [];

                for ($i = $months - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subMonths($i);
                    $startOfMonth = $date->copy()->startOfMonth();
                    $endOfMonth = $date->copy()->endOfMonth();

                    $transactions = $this->user->transactions()
                        ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                        ->get();

                    $income = $transactions->filter(fn($t) => $t->isCredit())->sum('amount');
                    $expenses = $transactions->filter(fn($t) => $t->isDebit())->sum('amount');

                    $labels[] = $date->format('M Y');
                    $data['income'][] = round($income, 2);
                    $data['expenses'][] = round($expenses, 2);
                    $data['net'][] = round($income - $expenses, 2);
                }

                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Income',
                            'data' => $data['income'],
                        ],
                        [
                            'label' => 'Expenses',
                            'data' => $data['expenses'],
                        ],
                    ],
                    'net' => $data['net'],
                ];
            }
        );
    }

    /**
     * Get top spending categories for current month
     */
    public function getTopCategories(int $limit = 5): array
    {
        return Cache::remember(
            "dashboard.top_categories.{$this->user->id}." . now()->format('Y-m'),
            $this->cacheDuration,
            function () use ($limit) {
                $startOfMonth = Carbon::now()->startOfMonth();

                $categories = $this->user->transactions()
                    ->where('transaction_date', '>=', $startOfMonth)
                    ->where('transaction_type', 'debit')
                    ->get()
                    ->groupBy(fn($t) => $t->user_category ?: $t->category)
                    ->map(function ($transactions, $category) {
                        return [
                            'category' => $category ?: 'Uncategorized',
                            'amount' => $transactions->sum('amount'),
                            'count' => $transactions->count(),
                        ];
                    })
                    ->sortByDesc('amount')
                    ->take($limit)
                    ->values();

                $total = $categories->sum('amount');

                return $categories->map(function ($cat) use ($total) {
                    $cat['percentage'] = $total > 0 ? round(($cat['amount'] / $total) * 100, 1) : 0;
                    $cat['formatted_amount'] = '$' . number_format($cat['amount'], 2);
                    return $cat;
                })->toArray();
            }
        );
    }

    /**
     * Get bills due this week
     */
    public function getBillsDueThisWeek(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        return $this->user->bills()
            ->whereBetween('next_due_date', [$startOfWeek, $endOfWeek])
            ->where('payment_status', '!=', 'paid')
            ->orderBy('next_due_date')
            ->get()
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'name' => $bill->name,
                    'amount' => $bill->amount,
                    'formatted_amount' => '$' . number_format($bill->amount, 2),
                    'due_date' => $bill->next_due_date,
                    'formatted_due_date' => $bill->next_due_date->format('M j'),
                    'days_until_due' => Carbon::now()->diffInDays($bill->next_due_date, false),
                    'is_overdue' => $bill->isOverdue(),
                    'is_due_soon' => $bill->isDueSoon(),
                    'category' => $bill->category,
                    'priority' => $bill->priority,
                ];
            })
            ->toArray();
    }

    /**
     * Get bills due before next payday
     */
    public function getBillsDueBeforePayday(): array
    {
        $paySchedule = $this->user->paySchedules()->where('is_active', true)->first();

        if (!$paySchedule) {
            return [];
        }

        $bills = $paySchedule->getBillsDueBeforePayday();
        $totalDue = $paySchedule->getTotalBillsDueBeforePayday();
        $availableAfterBills = $paySchedule->available_after_bills;

        return [
            'bills' => $bills->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'name' => $bill->name,
                    'amount' => $bill->amount,
                    'formatted_amount' => '$' . number_format($bill->amount, 2),
                    'due_date' => $bill->next_due_date,
                    'formatted_due_date' => $bill->next_due_date->format('M j'),
                    'category' => $bill->category,
                ];
            })->toArray(),
            'total_due' => $totalDue,
            'formatted_total_due' => '$' . number_format($totalDue, 2),
            'available_after_bills' => $availableAfterBills,
            'formatted_available_after_bills' => '$' . number_format($availableAfterBills, 2),
            'next_payday' => $paySchedule->next_pay_date,
        ];
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(int $limit = 10): array
    {
        return $this->user->transactions()
            ->with('account')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'merchant' => $transaction->merchant_name ?: $transaction->name,
                    'amount' => $transaction->amount,
                    'formatted_amount' => $transaction->formatted_amount,
                    'date' => $transaction->transaction_date,
                    'formatted_date' => $transaction->transaction_date->format('M j'),
                    'category' => $transaction->user_category ?: $transaction->category,
                    'account' => $transaction->account->account_name ?? null,
                    'type' => $transaction->transaction_type,
                    'is_debit' => $transaction->isDebit(),
                    'is_credit' => $transaction->isCredit(),
                ];
            })
            ->toArray();
    }

    /**
     * Clear cache for this user
     */
    public function clearCache(): void
    {
        Cache::tags(["user.{$this->user->id}"])->flush();
    }
}
