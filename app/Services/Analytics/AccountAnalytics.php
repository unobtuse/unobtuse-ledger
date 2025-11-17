<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AccountAnalytics
{
    protected User $user;
    protected int $cacheDuration = 900; // 15 minutes

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get account type distribution
     */
    public function getAccountTypeDistribution(): array
    {
        return Cache::remember(
            "account.type_distribution.{$this->user->id}",
            $this->cacheDuration,
            function () {
                $accounts = $this->user->accounts()->where('is_active', true)->get();

                $distribution = $accounts->groupBy('account_subtype')
                    ->map(function ($accounts, $type) {
                        return [
                            'type' => ucfirst(str_replace('_', ' ', $type)),
                            'count' => $accounts->count(),
                            'total_balance' => $accounts->sum('balance'),
                        ];
                    })
                    ->values();

                return [
                    'labels' => $distribution->pluck('type')->toArray(),
                    'data' => $distribution->pluck('total_balance')->map(fn($b) => abs($b))->toArray(),
                    'counts' => $distribution->pluck('count')->toArray(),
                ];
            }
        );
    }

    /**
     * Get balance trend for a specific account
     */
    public function getAccountBalanceTrend(Account $account, int $days = 30): array
    {
        return Cache::remember(
            "account.balance_trend.{$account->id}.{$days}",
            $this->cacheDuration,
            function () use ($account, $days) {
                $labels = [];
                $data = [];

                // Get current balance
                $currentBalance = $account->balance;

                // Calculate balance for each day by subtracting transactions going backward
                for ($i = 0; $i < $days; $i++) {
                    $date = Carbon::now()->subDays($i);
                    $labels[] = $date->format('M j');

                    // Get transactions after this date
                    $transactionsDelta = $account->transactions()
                        ->where('transaction_date', '>', $date->startOfDay())
                        ->get()
                        ->sum(function ($transaction) {
                            // For credits (income), subtract them when going backward
                            // For debits (expenses), add them back when going backward
                            return $transaction->isCredit() ? -$transaction->amount : $transaction->amount;
                        });

                    $balanceOnDate = $currentBalance - $transactionsDelta;
                    $data[] = round($balanceOnDate, 2);
                }

                return [
                    'labels' => array_reverse($labels),
                    'data' => array_reverse($data),
                ];
            }
        );
    }

    /**
     * Get balance trends for all accounts
     */
    public function getAllAccountBalanceTrends(int $days = 30): array
    {
        $accounts = $this->user->accounts()->where('is_active', true)->get();

        return $accounts->map(function ($account) use ($days) {
            $trend = $this->getAccountBalanceTrend($account, $days);

            return [
                'account_id' => $account->id,
                'account_name' => $account->nickname ?: $account->account_name,
                'account_type' => $account->account_subtype,
                'trend' => $trend['data'],
                'labels' => $trend['labels'],
            ];
        })->toArray();
    }

    /**
     * Get credit utilization for credit cards
     */
    public function getCreditUtilization(): array
    {
        return Cache::remember(
            "account.credit_utilization.{$this->user->id}",
            $this->cacheDuration,
            function () {
                $creditCards = $this->user->accounts()
                    ->where('account_subtype', 'credit_card')
                    ->where('is_active', true)
                    ->get();

                if ($creditCards->isEmpty()) {
                    return [
                        'total_utilization' => 0,
                        'total_balance' => 0,
                        'total_limit' => 0,
                        'cards' => [],
                    ];
                }

                $totalBalance = $creditCards->sum(fn($card) => abs($card->balance));
                $totalLimit = $creditCards->sum('credit_limit');
                $totalUtilization = $totalLimit > 0 ? ($totalBalance / $totalLimit) * 100 : 0;

                $cards = $creditCards->map(function ($card) {
                    $balance = abs($card->balance);
                    $limit = $card->credit_limit;
                    $utilization = $limit > 0 ? ($balance / $limit) * 100 : 0;

                    return [
                        'id' => $card->id,
                        'name' => $card->nickname ?: $card->account_name,
                        'balance' => $balance,
                        'formatted_balance' => '$' . number_format($balance, 2),
                        'limit' => $limit,
                        'formatted_limit' => '$' . number_format($limit, 2),
                        'available' => $limit - $balance,
                        'formatted_available' => '$' . number_format($limit - $balance, 2),
                        'utilization' => round($utilization, 1),
                        'status' => $this->getUtilizationStatus($utilization),
                    ];
                })->toArray();

                return [
                    'total_utilization' => round($totalUtilization, 1),
                    'total_balance' => $totalBalance,
                    'formatted_total_balance' => '$' . number_format($totalBalance, 2),
                    'total_limit' => $totalLimit,
                    'formatted_total_limit' => '$' . number_format($totalLimit, 2),
                    'total_available' => $totalLimit - $totalBalance,
                    'formatted_total_available' => '$' . number_format($totalLimit - $totalBalance, 2),
                    'cards' => $cards,
                ];
            }
        );
    }

    /**
     * Get utilization status based on percentage
     */
    protected function getUtilizationStatus(float $utilization): string
    {
        if ($utilization >= 80) {
            return 'critical';
        } elseif ($utilization >= 50) {
            return 'warning';
        } elseif ($utilization >= 30) {
            return 'moderate';
        } else {
            return 'good';
        }
    }

    /**
     * Get transaction volume by account
     */
    public function getTransactionVolumeByAccount(int $days = 30): array
    {
        return Cache::remember(
            "account.transaction_volume.{$this->user->id}.{$days}",
            $this->cacheDuration,
            function () use ($days) {
                $startDate = Carbon::now()->subDays($days);

                $accounts = $this->user->accounts()->where('is_active', true)->get();

                $data = $accounts->map(function ($account) use ($startDate) {
                    $count = $account->transactions()
                        ->where('transaction_date', '>=', $startDate)
                        ->count();

                    return [
                        'account' => $account->nickname ?: $account->account_name,
                        'count' => $count,
                    ];
                })
                ->sortByDesc('count')
                ->values();

                return [
                    'labels' => $data->pluck('account')->toArray(),
                    'data' => $data->pluck('count')->toArray(),
                ];
            }
        );
    }

    /**
     * Get accounts needing attention
     */
    public function getAccountsNeedingAttention(): array
    {
        $accounts = $this->user->accounts()->where('is_active', true)->get();

        $needingAttention = $accounts->filter(function ($account) {
            // Check if needs sync
            if ($account->needsSync()) {
                return true;
            }

            // Check if credit card is highly utilized
            if ($account->isCreditCard()) {
                $utilization = $account->credit_limit > 0
                    ? (abs($account->balance) / $account->credit_limit) * 100
                    : 0;
                if ($utilization >= 80) {
                    return true;
                }
            }

            // Check if payment is due soon
            if ($account->hasPaymentDue()) {
                return true;
            }

            // Check for low balance on checking/savings
            if (in_array($account->account_subtype, ['checking', 'savings'])) {
                if ($account->available_balance < 100) {
                    return true;
                }
            }

            return false;
        });

        return $needingAttention->map(function ($account) {
            $issues = [];

            if ($account->needsSync()) {
                $issues[] = 'Needs sync';
            }

            if ($account->isCreditCard()) {
                $utilization = $account->credit_limit > 0
                    ? (abs($account->balance) / $account->credit_limit) * 100
                    : 0;
                if ($utilization >= 80) {
                    $issues[] = 'High utilization';
                }
            }

            if ($account->hasPaymentDue()) {
                $issues[] = 'Payment due ' . $account->formatted_due_date;
            }

            if (in_array($account->account_subtype, ['checking', 'savings'])) {
                if ($account->available_balance < 100) {
                    $issues[] = 'Low balance';
                }
            }

            return [
                'id' => $account->id,
                'name' => $account->nickname ?: $account->account_name,
                'type' => $account->account_subtype,
                'issues' => $issues,
            ];
        })->values()->toArray();
    }

    /**
     * Get net worth trend over time (12 months)
     */
    public function getNetWorthTrend(int $months = 12): array
    {
        return Cache::remember(
            "account.networth_trend.{$this->user->id}.{$months}",
            $this->cacheDuration,
            function () use ($months) {
                $labels = [];
                $data = [];

                for ($i = $months - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subMonths($i);
                    $labels[] = $date->format('M Y');

                    // Calculate net worth for this month
                    $netWorth = $this->calculateNetWorthForDate($date);
                    $data[] = round($netWorth, 2);
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            }
        );
    }

    /**
     * Calculate net worth for a specific date
     */
    protected function calculateNetWorthForDate(Carbon $date): float
    {
        $accounts = $this->user->accounts()->where('is_active', true)->get();

        // Get current net worth
        $currentAssets = $accounts->whereIn('account_subtype', ['checking', 'savings', 'investment'])
            ->sum('balance');
        $currentLiabilities = abs($accounts->whereIn('account_subtype', ['credit_card', 'loan'])
            ->sum('balance'));
        $currentNetWorth = $currentAssets - $currentLiabilities;

        // Calculate transaction delta since the date
        $transactionsDelta = $this->user->transactions()
            ->where('transaction_date', '>', $date->endOfMonth())
            ->get()
            ->sum(function ($transaction) {
                // Credits increase net worth, debits decrease it
                return $transaction->isCredit() ? -$transaction->amount : $transaction->amount;
            });

        return $currentNetWorth - $transactionsDelta;
    }

    /**
     * Clear cache for this user
     */
    public function clearCache(): void
    {
        Cache::tags(["user.{$this->user->id}"])->flush();
    }
}
