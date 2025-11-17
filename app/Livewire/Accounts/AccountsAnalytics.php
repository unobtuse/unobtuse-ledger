<?php

declare(strict_types=1);

namespace App\Livewire\Accounts;

use App\Services\Analytics\AccountAnalytics;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Accounts Analytics Livewire Component
 * 
 * Displays comprehensive analytics and visualizations for accounts:
 * - Net worth trend (12 months)
 * - Account balance trends (multi-line)
 * - Account type distribution
 * - Credit utilization gauges
 * - Transaction volume by account
 * - Account health indicators
 */
class AccountsAnalytics extends Component
{
    /**
     * Render the component
     */
    public function render(): View
    {
        $user = auth()->user();
        $analytics = new AccountAnalytics($user);

        return view('livewire.accounts.accounts-analytics', [
            'netWorthTrend' => $analytics->getNetWorthTrend(12),
            'accountBalanceTrends' => $analytics->getAllAccountBalanceTrends(30),
            'accountTypeDistribution' => $analytics->getAccountTypeDistribution(),
            'creditUtilization' => $analytics->getCreditUtilization(),
            'transactionVolume' => $analytics->getTransactionVolumeByAccount(30),
            'accountsNeedingAttention' => $analytics->getAccountsNeedingAttention(),
        ]);
    }
}

