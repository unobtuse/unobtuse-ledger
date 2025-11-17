<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Services\Analytics\DashboardMetrics;
use App\Services\Analytics\AccountAnalytics;
use App\Services\Analytics\FinancialHealthCalculator;
use Illuminate\Support\Facades\Auth;

class FinancialOverview extends Component
{
    public $netWorth;
    public $cashFlow;
    public $availableCash;
    public $nextPayday;
    public $spendingTrend;
    public $topCategories;
    public $billsDueThisWeek;
    public $billsDueBeforePayday;
    public $recentTransactions;
    public $financialHealthScore;
    public $creditUtilization;
    public $accountsWithSparklines;

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $user = Auth::user();
        $dashboardMetrics = new DashboardMetrics($user);
        $accountAnalytics = new AccountAnalytics($user);
        $healthCalculator = new FinancialHealthCalculator($user);

        // Section 1: Financial Snapshot
        $this->netWorth = $dashboardMetrics->getNetWorth();
        $this->cashFlow = $dashboardMetrics->getCurrentMonthCashFlow();
        $this->availableCash = $dashboardMetrics->getAvailableCash();
        $this->nextPayday = $dashboardMetrics->getNextPayday();

        // Section 2: Spending Intelligence
        $this->spendingTrend = $dashboardMetrics->getSpendingTrend(12);
        $this->topCategories = $dashboardMetrics->getTopCategories(5);

        // Section 3: Bills & Obligations
        $this->billsDueThisWeek = $dashboardMetrics->getBillsDueThisWeek();
        $this->billsDueBeforePayday = $dashboardMetrics->getBillsDueBeforePayday();

        // Section 4: Account Overview
        $this->creditUtilization = $accountAnalytics->getCreditUtilization();
        $this->accountsWithSparklines = $this->getAccountsWithSparklines($accountAnalytics);

        // Section 5: Budget Status (will get from current budget)
        // This will be loaded in the view from the user's current budget

        // Section 6: Financial Health Score
        $this->financialHealthScore = $healthCalculator->calculateHealthScore();

        // Section 7: Recent Activity
        $this->recentTransactions = $dashboardMetrics->getRecentTransactions(10);
    }

    protected function getAccountsWithSparklines(AccountAnalytics $accountAnalytics)
    {
        $user = Auth::user();
        $accounts = $user->accounts()->where('is_active', true)->get();

        return $accounts->map(function ($account) use ($accountAnalytics) {
            $trend = $accountAnalytics->getAccountBalanceTrend($account, 30);

            return [
                'id' => $account->id,
                'name' => $account->nickname ?: $account->account_name,
                'type' => $account->account_subtype,
                'balance' => $account->balance,
                'formatted_balance' => '$' . number_format($account->balance, 2),
                'available_balance' => $account->available_balance,
                'formatted_available' => '$' . number_format($account->available_balance, 2),
                'trend_data' => $trend['data'],
                'trend_labels' => $trend['labels'],
                'is_credit_card' => $account->isCreditCard(),
                'credit_limit' => $account->credit_limit,
                'utilization' => $account->isCreditCard() && $account->credit_limit > 0
                    ? round((abs($account->balance) / $account->credit_limit) * 100, 1)
                    : null,
                'needs_sync' => $account->needsSync(),
                'last_synced' => $account->last_synced_at?->diffForHumans(),
            ];
        })->toArray();
    }

    public function refreshData()
    {
        $this->loadDashboardData();
        $this->dispatch('dashboard-refreshed');
    }

    public function render()
    {
        // Get current budget for Section 5
        $currentBudget = Auth::user()->budgets()
            ->where('month', now()->format('Y-m'))
            ->first();

        return view('livewire.dashboard.financial-overview', [
            'currentBudget' => $currentBudget,
        ]);
    }
}
