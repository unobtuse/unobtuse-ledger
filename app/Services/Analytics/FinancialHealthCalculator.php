<?php

namespace App\Services\Analytics;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FinancialHealthCalculator
{
    protected User $user;
    protected int $cacheDuration = 900; // 15 minutes

    // Weight distribution for health score
    protected const WEIGHTS = [
        'savings_rate' => 30,
        'budget_adherence' => 25,
        'bill_payment' => 20,
        'debt_to_income' => 15,
        'account_diversity' => 10,
    ];

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Calculate overall financial health score (0-100)
     */
    public function calculateHealthScore(): array
    {
        return Cache::remember(
            "financial_health.score.{$this->user->id}",
            $this->cacheDuration,
            function () {
                $components = [
                    'savings_rate' => $this->calculateSavingsRateScore(),
                    'budget_adherence' => $this->calculateBudgetAdherenceScore(),
                    'bill_payment' => $this->calculateBillPaymentScore(),
                    'debt_to_income' => $this->calculateDebtToIncomeScore(),
                    'account_diversity' => $this->calculateAccountDiversityScore(),
                ];

                // Calculate weighted total
                $totalScore = 0;
                foreach ($components as $key => $component) {
                    $totalScore += $component['score'] * (self::WEIGHTS[$key] / 100);
                }

                $totalScore = round($totalScore, 1);

                // Get trend from last month
                $trend = $this->calculateTrend($totalScore);

                return [
                    'total_score' => $totalScore,
                    'grade' => $this->getGrade($totalScore),
                    'status' => $this->getStatus($totalScore),
                    'components' => $components,
                    'trend' => $trend,
                ];
            }
        );
    }

    /**
     * Calculate savings rate score (0-100)
     * Based on percentage of income being saved
     */
    protected function calculateSavingsRateScore(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $transactions = $this->user->transactions()
            ->where('transaction_date', '>=', $startOfMonth)
            ->get();

        $income = $transactions->filter(fn($t) => $t->isCredit())->sum('amount');
        $expenses = $transactions->filter(fn($t) => $t->isDebit())->sum('amount');

        $savingsRate = $income > 0 ? (($income - $expenses) / $income) * 100 : 0;

        // Score:
        // 20%+ savings = 100
        // 15-20% = 80
        // 10-15% = 60
        // 5-10% = 40
        // 0-5% = 20
        // Negative = 0
        $score = match(true) {
            $savingsRate >= 20 => 100,
            $savingsRate >= 15 => 80 + (($savingsRate - 15) / 5) * 20,
            $savingsRate >= 10 => 60 + (($savingsRate - 10) / 5) * 20,
            $savingsRate >= 5 => 40 + (($savingsRate - 5) / 5) * 20,
            $savingsRate >= 0 => 20 + ($savingsRate / 5) * 20,
            default => 0,
        };

        return [
            'score' => round($score, 1),
            'raw_value' => round($savingsRate, 1),
            'formatted_value' => round($savingsRate, 1) . '%',
            'description' => 'Percentage of income saved',
            'status' => $this->getSavingsRateStatus($savingsRate),
        ];
    }

    /**
     * Calculate budget adherence score (0-100)
     * Based on staying within budget categories
     */
    protected function calculateBudgetAdherenceScore(): array
    {
        $currentBudget = $this->user->budgets()
            ->where('month', Carbon::now()->format('Y-m'))
            ->first();

        if (!$currentBudget || !$currentBudget->category_breakdown) {
            return [
                'score' => 50, // Neutral score if no budget
                'raw_value' => 0,
                'formatted_value' => 'No budget set',
                'description' => 'Budget adherence',
                'status' => 'no_budget',
            ];
        }

        $categoryBreakdown = $currentBudget->category_breakdown;
        $categoryLimits = $currentBudget->metadata['category_limits'] ?? [];

        if (empty($categoryLimits)) {
            return [
                'score' => 50,
                'raw_value' => 0,
                'formatted_value' => 'No limits set',
                'description' => 'Budget adherence',
                'status' => 'no_limits',
            ];
        }

        $categoriesWithinBudget = 0;
        $totalCategories = 0;

        foreach ($categoryLimits as $category => $limit) {
            $spent = $categoryBreakdown[$category] ?? 0;
            $totalCategories++;

            if ($spent <= $limit) {
                $categoriesWithinBudget++;
            }
        }

        $adherenceRate = $totalCategories > 0
            ? ($categoriesWithinBudget / $totalCategories) * 100
            : 0;

        // Direct mapping: adherence rate = score
        $score = $adherenceRate;

        return [
            'score' => round($score, 1),
            'raw_value' => round($adherenceRate, 1),
            'formatted_value' => round($adherenceRate, 1) . '%',
            'description' => 'Categories within budget',
            'status' => $this->getBudgetAdherenceStatus($adherenceRate),
        ];
    }

    /**
     * Calculate bill payment score (0-100)
     * Based on on-time payment history
     */
    protected function calculateBillPaymentScore(): array
    {
        $billAnalytics = new BillAnalytics($this->user);
        $reliability = $billAnalytics->getPaymentReliability();

        $score = $reliability['score'];

        return [
            'score' => $score,
            'raw_value' => $score,
            'formatted_value' => $score . '%',
            'description' => 'On-time bill payments',
            'status' => $reliability['status'],
        ];
    }

    /**
     * Calculate debt-to-income ratio score (0-100)
     * Lower debt-to-income is better
     */
    protected function calculateDebtToIncomeScore(): array
    {
        // Get monthly income (average from last 3 months)
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        $totalIncome = $this->user->transactions()
            ->where('transaction_date', '>=', $threeMonthsAgo)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        $monthlyIncome = $totalIncome / 3;

        // Get monthly debt obligations
        $monthlyDebt = $this->user->bills()->get()->sum(function ($bill) {
            $multiplier = match($bill->frequency) {
                'weekly' => 4.33,
                'biweekly' => 2.17,
                'monthly' => 1,
                'quarterly' => 0.33,
                'annual' => 0.083,
                default => 1,
            };
            return $bill->amount * $multiplier;
        });

        // Add credit card minimum payments
        $creditCards = $this->user->accounts()
            ->where('account_subtype', 'credit_card')
            ->where('is_active', true)
            ->get();

        foreach ($creditCards as $card) {
            if ($card->minimum_payment_amount) {
                $monthlyDebt += $card->minimum_payment_amount;
            }
        }

        $dtiRatio = $monthlyIncome > 0 ? ($monthlyDebt / $monthlyIncome) * 100 : 0;

        // Score:
        // 0-20% DTI = 100
        // 20-36% = 70
        // 36-43% = 40
        // 43%+ = 0
        $score = match(true) {
            $dtiRatio <= 20 => 100,
            $dtiRatio <= 36 => 70 + ((36 - $dtiRatio) / 16) * 30,
            $dtiRatio <= 43 => 40 + ((43 - $dtiRatio) / 7) * 30,
            default => max(0, 40 - (($dtiRatio - 43) * 2)),
        };

        return [
            'score' => round($score, 1),
            'raw_value' => round($dtiRatio, 1),
            'formatted_value' => round($dtiRatio, 1) . '%',
            'description' => 'Debt-to-income ratio',
            'status' => $this->getDebtToIncomeStatus($dtiRatio),
        ];
    }

    /**
     * Calculate account diversity score (0-100)
     * Having different account types is healthier
     */
    protected function calculateAccountDiversityScore(): array
    {
        $accounts = $this->user->accounts()->where('is_active', true)->get();
        $uniqueTypes = $accounts->pluck('account_subtype')->unique()->count();

        // Score based on diversity:
        // 4+ types = 100
        // 3 types = 75
        // 2 types = 50
        // 1 type = 25
        // 0 types = 0
        $score = min(100, $uniqueTypes * 25);

        $types = $accounts->pluck('account_subtype')->unique()->values()->toArray();

        return [
            'score' => $score,
            'raw_value' => $uniqueTypes,
            'formatted_value' => $uniqueTypes . ' types',
            'description' => 'Account type diversity',
            'status' => $uniqueTypes >= 3 ? 'good' : ($uniqueTypes >= 2 ? 'fair' : 'limited'),
            'types' => $types,
        ];
    }

    /**
     * Calculate trend from previous month
     */
    protected function calculateTrend(float $currentScore): array
    {
        $lastMonthKey = "financial_health.score.{$this->user->id}.last_month";
        $lastMonthScore = Cache::get($lastMonthKey);

        // Store current score for next month's comparison
        Cache::put($lastMonthKey, $currentScore, now()->addMonth());

        if ($lastMonthScore === null) {
            return [
                'direction' => 'stable',
                'change' => 0,
                'previous_score' => null,
            ];
        }

        $change = $currentScore - $lastMonthScore;

        return [
            'direction' => $change > 2 ? 'up' : ($change < -2 ? 'down' : 'stable'),
            'change' => round($change, 1),
            'previous_score' => $lastMonthScore,
        ];
    }

    /**
     * Get letter grade from score
     */
    protected function getGrade(float $score): string
    {
        return match(true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Get status label from score
     */
    protected function getStatus(float $score): string
    {
        return match(true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 55 => 'fair',
            $score >= 40 => 'needs_improvement',
            default => 'critical',
        };
    }

    /**
     * Helper status methods
     */
    protected function getSavingsRateStatus(float $rate): string
    {
        return match(true) {
            $rate >= 20 => 'excellent',
            $rate >= 10 => 'good',
            $rate >= 5 => 'fair',
            $rate >= 0 => 'low',
            default => 'negative',
        };
    }

    protected function getBudgetAdherenceStatus(float $rate): string
    {
        return match(true) {
            $rate >= 90 => 'excellent',
            $rate >= 75 => 'good',
            $rate >= 60 => 'fair',
            default => 'poor',
        };
    }

    protected function getDebtToIncomeStatus(float $ratio): string
    {
        return match(true) {
            $ratio <= 20 => 'excellent',
            $ratio <= 36 => 'good',
            $ratio <= 43 => 'fair',
            default => 'high',
        };
    }

    /**
     * Clear cache for this user
     */
    public function clearCache(): void
    {
        Cache::forget("financial_health.score.{$this->user->id}");
        Cache::forget("financial_health.score.{$this->user->id}.last_month");
    }
}
