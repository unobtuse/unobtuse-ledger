<?php

declare(strict_types=1);

namespace App\Livewire\Budget;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Budget Dashboard Livewire Component
 * 
 * Provides budget overview with income vs expenses tracking,
 * category-based spending analysis, and budget alerts.
 */
class BudgetDashboard extends Component
{
    public string $selectedMonth;
    public string $selectedYear;
    
    /**
     * Mount component
     */
    public function mount(): void
    {
        $this->selectedMonth = now()->format('m');
        $this->selectedYear = now()->format('Y');
    }
    
    /**
     * Get current budget for selected period
     */
    protected function getCurrentBudget(): ?Budget
    {
        $startDate = Carbon::createFromFormat('Y-m', $this->selectedYear . '-' . $this->selectedMonth)->startOfMonth();
        
        return Budget::where('user_id', auth()->id())
            ->where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $startDate)
            ->first();
    }
    
    /**
     * Get transactions for selected month
     */
    protected function getTransactions()
    {
        $startDate = Carbon::createFromFormat('Y-m', $this->selectedYear . '-' . $this->selectedMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        return Transaction::where('user_id', auth()->id())
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
    }
    
    /**
     * Calculate income and expenses summary
     */
    protected function getIncomeExpensesSummary(): array
    {
        $transactions = $this->getTransactions();
        
        $totalIncome = $transactions->where('amount', '>', 0)->sum('amount');
        $totalExpenses = abs($transactions->where('amount', '<', 0)->sum('amount'));
        $netChange = $totalIncome - $totalExpenses;
        $savingsRate = $totalIncome > 0 ? ($netChange / $totalIncome) * 100 : 0;
        
        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_change' => $netChange,
            'savings_rate' => $savingsRate,
        ];
    }
    
    /**
     * Get spending by category
     */
    protected function getSpendingByCategory(): array
    {
        $transactions = $this->getTransactions()->where('amount', '<', 0);
        
        $categorySpending = [];
        
        foreach ($transactions as $transaction) {
            if ($transaction->categories && is_array($transaction->categories)) {
                foreach ($transaction->categories as $category) {
                    if (!isset($categorySpending[$category])) {
                        $categorySpending[$category] = 0;
                    }
                    $categorySpending[$category] += abs($transaction->amount);
                }
            } else {
                if (!isset($categorySpending['Uncategorized'])) {
                    $categorySpending['Uncategorized'] = 0;
                }
                $categorySpending['Uncategorized'] += abs($transaction->amount);
            }
        }
        
        // Sort by spending amount (highest first)
        arsort($categorySpending);
        
        return $categorySpending;
    }
    
    /**
     * Get budget progress for each category
     */
    protected function getCategoryBudgets(): array
    {
        $budget = $this->getCurrentBudget();
        $spendingByCategory = $this->getSpendingByCategory();
        
        if (!$budget || !$budget->category_limits) {
            return [];
        }
        
        $categoryBudgets = [];
        
        foreach ($budget->category_limits as $category => $limit) {
            $spent = $spendingByCategory[$category] ?? 0;
            $remaining = $limit - $spent;
            $percentage = $limit > 0 ? ($spent / $limit) * 100 : 0;
            
            $categoryBudgets[] = [
                'category' => $category,
                'limit' => $limit,
                'spent' => $spent,
                'remaining' => $remaining,
                'percentage' => min($percentage, 100),
                'is_over_budget' => $spent > $limit,
            ];
        }
        
        // Sort by percentage (highest first)
        usort($categoryBudgets, function ($a, $b) {
            return $b['percentage'] <=> $a['percentage'];
        });
        
        return $categoryBudgets;
    }
    
    /**
     * Get budget alerts
     */
    protected function getBudgetAlerts(): array
    {
        $alerts = [];
        $categoryBudgets = $this->getCategoryBudgets();
        
        foreach ($categoryBudgets as $categoryBudget) {
            if ($categoryBudget['is_over_budget']) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => "You've exceeded your {$categoryBudget['category']} budget by $" . number_format($categoryBudget['spent'] - $categoryBudget['limit'], 2),
                ];
            } elseif ($categoryBudget['percentage'] >= 90) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "You've used {$categoryBudget['percentage']}% of your {$categoryBudget['category']} budget",
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get spending trend (compare to previous month)
     */
    protected function getSpendingTrend(): array
    {
        $currentMonth = Carbon::createFromFormat('Y-m', $this->selectedYear . '-' . $this->selectedMonth);
        $previousMonth = $currentMonth->copy()->subMonth();
        
        // Current month transactions
        $currentTransactions = Transaction::where('user_id', auth()->id())
            ->whereBetween('date', [$currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth()])
            ->get();
        
        // Previous month transactions
        $previousTransactions = Transaction::where('user_id', auth()->id())
            ->whereBetween('date', [$previousMonth->copy()->startOfMonth(), $previousMonth->copy()->endOfMonth()])
            ->get();
        
        $currentExpenses = abs($currentTransactions->where('amount', '<', 0)->sum('amount'));
        $previousExpenses = abs($previousTransactions->where('amount', '<', 0)->sum('amount'));
        
        $change = $currentExpenses - $previousExpenses;
        $changePercentage = $previousExpenses > 0 ? ($change / $previousExpenses) * 100 : 0;
        
        return [
            'current_expenses' => $currentExpenses,
            'previous_expenses' => $previousExpenses,
            'change' => $change,
            'change_percentage' => $changePercentage,
        ];
    }
    
    /**
     * Get available months for selection
     */
    protected function getAvailableMonths(): array
    {
        return [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];
    }
    
    /**
     * Get available years
     */
    protected function getAvailableYears(): array
    {
        $currentYear = (int) now()->format('Y');
        $years = [];
        
        for ($i = $currentYear - 2; $i <= $currentYear; $i++) {
            $years[] = (string) $i;
        }
        
        return $years;
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        return view('livewire.budget.budget-dashboard', [
            'currentBudget' => $this->getCurrentBudget(),
            'incomeExpensesSummary' => $this->getIncomeExpensesSummary(),
            'spendingByCategory' => $this->getSpendingByCategory(),
            'categoryBudgets' => $this->getCategoryBudgets(),
            'budgetAlerts' => $this->getBudgetAlerts(),
            'spendingTrend' => $this->getSpendingTrend(),
            'availableMonths' => $this->getAvailableMonths(),
            'availableYears' => $this->getAvailableYears(),
        ]);
    }
}


