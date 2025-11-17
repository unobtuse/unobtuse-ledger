<?php

namespace App\Services\Analytics;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TransactionAnalytics
{
    protected User $user;
    protected int $cacheDuration = 900; // 15 minutes

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get spending by category
     */
    public function getSpendingByCategory(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        $cacheKey = "transaction.spending_by_category.{$this->user->id}."
            . $startDate->format('Ymd') . '.' . $endDate->format('Ymd');

        return Cache::remember(
            $cacheKey,
            $this->cacheDuration,
            function () use ($startDate, $endDate) {
                $transactions = $this->user->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->where('transaction_type', 'debit')
                    ->get();

                $byCategory = $transactions->groupBy(fn($t) => $t->user_category ?: $t->category)
                    ->map(function ($transactions, $category) {
                        return [
                            'category' => $category ?: 'Uncategorized',
                            'amount' => $transactions->sum('amount'),
                            'count' => $transactions->count(),
                        ];
                    })
                    ->sortByDesc('amount')
                    ->values();

                $total = $byCategory->sum('amount');

                return [
                    'labels' => $byCategory->pluck('category')->toArray(),
                    'data' => $byCategory->pluck('amount')->toArray(),
                    'counts' => $byCategory->pluck('count')->toArray(),
                    'percentages' => $byCategory->map(function ($cat) use ($total) {
                        return $total > 0 ? round(($cat['amount'] / $total) * 100, 1) : 0;
                    })->toArray(),
                    'total' => $total,
                ];
            }
        );
    }

    /**
     * Get daily spending pattern for a date range
     */
    public function getDailySpending(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        $cacheKey = "transaction.daily_spending.{$this->user->id}."
            . $startDate->format('Ymd') . '.' . $endDate->format('Ymd');

        return Cache::remember(
            $cacheKey,
            $this->cacheDuration,
            function () use ($startDate, $endDate) {
                $transactions = $this->user->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->get();

                $days = $startDate->diffInDays($endDate) + 1;
                $labels = [];
                $incomeData = [];
                $expenseData = [];

                for ($i = 0; $i < $days; $i++) {
                    $date = $startDate->copy()->addDays($i);
                    $labels[] = $date->format('M j');

                    $dayTransactions = $transactions->filter(function ($t) use ($date) {
                        return $t->transaction_date->isSameDay($date);
                    });

                    $incomeData[] = $dayTransactions->filter(fn($t) => $t->isCredit())->sum('amount');
                    $expenseData[] = $dayTransactions->filter(fn($t) => $t->isDebit())->sum('amount');
                }

                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Income',
                            'data' => $incomeData,
                        ],
                        [
                            'label' => 'Expenses',
                            'data' => $expenseData,
                        ],
                    ],
                ];
            }
        );
    }

    /**
     * Get top merchants by spending
     */
    public function getTopMerchants(int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        $cacheKey = "transaction.top_merchants.{$this->user->id}."
            . $startDate->format('Ymd') . '.' . $endDate->format('Ymd') . ".{$limit}";

        return Cache::remember(
            $cacheKey,
            $this->cacheDuration,
            function () use ($limit, $startDate, $endDate) {
                $transactions = $this->user->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->where('transaction_type', 'debit')
                    ->get();

                $byMerchant = $transactions->groupBy(fn($t) => $t->merchant_name ?: $t->name)
                    ->map(function ($transactions, $merchant) {
                        return [
                            'merchant' => $merchant,
                            'amount' => $transactions->sum('amount'),
                            'count' => $transactions->count(),
                            'average' => $transactions->avg('amount'),
                        ];
                    })
                    ->sortByDesc('amount')
                    ->take($limit)
                    ->values();

                return [
                    'labels' => $byMerchant->pluck('merchant')->toArray(),
                    'data' => $byMerchant->pluck('amount')->toArray(),
                    'counts' => $byMerchant->pluck('count')->toArray(),
                    'averages' => $byMerchant->pluck('average')->map(fn($a) => round($a, 2))->toArray(),
                ];
            }
        );
    }

    /**
     * Get payment channel breakdown (online, in-store, other)
     */
    public function getPaymentChannelBreakdown(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        $cacheKey = "transaction.payment_channel.{$this->user->id}."
            . $startDate->format('Ymd') . '.' . $endDate->format('Ymd');

        return Cache::remember(
            $cacheKey,
            $this->cacheDuration,
            function () use ($startDate, $endDate) {
                $transactions = $this->user->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->where('transaction_type', 'debit')
                    ->get();

                $byChannel = $transactions->groupBy('payment_channel')
                    ->map(function ($transactions, $channel) {
                        return [
                            'channel' => ucfirst($channel ?: 'Other'),
                            'amount' => $transactions->sum('amount'),
                            'count' => $transactions->count(),
                        ];
                    })
                    ->sortByDesc('amount')
                    ->values();

                return [
                    'labels' => $byChannel->pluck('channel')->toArray(),
                    'data' => $byChannel->pluck('amount')->toArray(),
                    'counts' => $byChannel->pluck('count')->toArray(),
                ];
            }
        );
    }

    /**
     * Get recurring transactions summary
     */
    public function getRecurringTransactions(): array
    {
        return Cache::remember(
            "transaction.recurring.{$this->user->id}",
            $this->cacheDuration,
            function () {
                $recurring = $this->user->transactions()
                    ->where('is_recurring', true)
                    ->get();

                $byGroup = $recurring->groupBy('recurring_group_id')
                    ->map(function ($transactions) {
                        $first = $transactions->first();
                        return [
                            'merchant' => $first->merchant_name ?: $first->name,
                            'amount' => $transactions->avg('amount'),
                            'frequency' => $first->recurring_frequency,
                            'count' => $transactions->count(),
                            'last_date' => $transactions->max('transaction_date'),
                        ];
                    })
                    ->sortByDesc('amount')
                    ->values();

                $monthlyTotal = $byGroup->sum(function ($item) {
                    $multiplier = match($item['frequency']) {
                        'weekly' => 4.33,
                        'biweekly' => 2.17,
                        'monthly' => 1,
                        'quarterly' => 0.33,
                        'annual' => 0.083,
                        default => 1,
                    };
                    return $item['amount'] * $multiplier;
                });

                return [
                    'groups' => $byGroup->toArray(),
                    'total_groups' => $byGroup->count(),
                    'estimated_monthly_total' => round($monthlyTotal, 2),
                    'formatted_monthly_total' => '$' . number_format($monthlyTotal, 2),
                ];
            }
        );
    }

    /**
     * Get spending heatmap data (by day of month)
     */
    public function getSpendingHeatmap(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        $transactions = $this->user->transactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('transaction_type', 'debit')
            ->get();

        $heatmap = [];
        $maxAmount = 0;

        for ($i = 0; $i < $startDate->diffInDays($endDate) + 1; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayTransactions = $transactions->filter(function ($t) use ($date) {
                return $t->transaction_date->isSameDay($date);
            });

            $amount = $dayTransactions->sum('amount');
            $maxAmount = max($maxAmount, $amount);

            $heatmap[] = [
                'date' => $date->format('Y-m-d'),
                'formatted_date' => $date->format('M j'),
                'day_of_month' => $date->day,
                'day_of_week' => $date->dayOfWeek,
                'amount' => $amount,
                'count' => $dayTransactions->count(),
            ];
        }

        // Calculate intensity (0-1) for each day
        foreach ($heatmap as &$day) {
            $day['intensity'] = $maxAmount > 0 ? $day['amount'] / $maxAmount : 0;
        }

        return $heatmap;
    }

    /**
     * Get income vs expenses running balance
     */
    public function getRunningBalance(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        $transactions = $this->user->transactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        $labels = [];
        $balanceData = [];
        $runningBalance = 0;

        $groupedByDate = $transactions->groupBy(function ($t) {
            return $t->transaction_date->format('Y-m-d');
        });

        foreach ($groupedByDate as $date => $dayTransactions) {
            $income = $dayTransactions->filter(fn($t) => $t->isCredit())->sum('amount');
            $expenses = $dayTransactions->filter(fn($t) => $t->isDebit())->sum('amount');
            $runningBalance += ($income - $expenses);

            $labels[] = Carbon::parse($date)->format('M j');
            $balanceData[] = round($runningBalance, 2);
        }

        return [
            'labels' => $labels,
            'data' => $balanceData,
        ];
    }

    /**
     * Clear cache for this user
     */
    public function clearCache(): void
    {
        Cache::tags(["user.{$this->user->id}"])->flush();
    }
}
