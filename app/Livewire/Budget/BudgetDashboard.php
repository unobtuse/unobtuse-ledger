<?php

declare(strict_types=1);

namespace App\Livewire\Budget;

use App\Models\Bill;
use App\Models\Budget;
use App\Models\PaySchedule;
use App\Models\Transaction;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Budget Dashboard Livewire Component
 * 
 * Provides comprehensive budget overview with income vs expenses tracking,
 * category-based spending analysis, budget alerts, pay schedule integration,
 * and budget management capabilities.
 */
class BudgetDashboard extends Component
{
    public string $selectedMonth;
    public string $selectedYear;
    
    // Budget editing modal state
    public bool $showEditModal = false;
    public ?string $editingBudgetId = null;
    public array $categoryLimits = [];
    public ?float $expectedIncome = null;
    public ?float $savingsGoal = null;
    public ?float $spendingLimit = null;
    public bool $autoCalculate = true;
    
    protected BudgetService $budgetService;
    
    /**
     * Mount component
     */
    public function mount(): void
    {
        $this->selectedMonth = now()->format('m');
        $this->selectedYear = now()->format('Y');
        $this->budgetService = app(BudgetService::class);
    }
    
    /**
     * Get current budget for selected period
     */
    protected function getCurrentBudget(): ?Budget
    {
        $month = $this->selectedYear . '-' . $this->selectedMonth;
        
        $budget = Budget::where('user_id', auth()->id())
            ->where('month', $month)
            ->first();
        
        // Auto-calculate if budget doesn't exist
        if (!$budget) {
            $budget = $this->budgetService->calculateBudget(auth()->user(), $month);
        }
        
        return $budget;
    }
    
    /**
     * Get transactions for selected month
     */
    protected function getTransactions()
    {
        $startDate = Carbon::createFromFormat('Y-m', $this->selectedYear . '-' . $this->selectedMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        return Transaction::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
    }
    
    /**
     * Calculate income and expenses summary
     */
    protected function getIncomeExpensesSummary(): array
    {
        $budget = $this->getCurrentBudget();
        $transactions = $this->getTransactions();
        
        // Use budget income if available, otherwise calculate from transactions
        $totalIncome = $budget?->total_income ?? $transactions->where('amount', '>', 0)->sum('amount');
        $totalExpenses = abs($transactions->where('amount', '<', 0)->sum('amount'));
        $netChange = $totalIncome - $totalExpenses;
        $savingsRate = $totalIncome > 0 ? ($netChange / $totalIncome) * 100 : 0;
        
        return [
            'total_income' => (float) $totalIncome,
            'total_expenses' => (float) $totalExpenses,
            'net_change' => (float) $netChange,
            'savings_rate' => (float) $savingsRate,
            'remaining_budget' => (float) ($budget?->remaining_budget ?? $netChange),
        ];
    }
    
    /**
     * Get spending by category
     */
    protected function getSpendingByCategory(): array
    {
        $budget = $this->getCurrentBudget();
        $transactions = $this->getTransactions()->where('amount', '<', 0);
        
        // Use budget category breakdown if available, but ensure positive values
        if ($budget && $budget->category_breakdown) {
            $breakdown = [];
            foreach ($budget->category_breakdown as $category => $amount) {
                $breakdown[$category] = abs((float) $amount);
            }
            // Sort by spending amount (highest first)
            arsort($breakdown);
            return $breakdown;
        }
        
        $categorySpending = [];
        
        foreach ($transactions as $transaction) {
            $category = $transaction->user_category ?? $transaction->category ?? 'Uncategorized';
            
            if (!isset($categorySpending[$category])) {
                $categorySpending[$category] = 0;
            }
            $categorySpending[$category] += abs((float) $transaction->amount);
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
        
        if (!$budget) {
            return [];
        }
        
        // Get category limits from metadata
        $categoryLimits = $budget->metadata['category_limits'] ?? [];
        
        if (empty($categoryLimits)) {
            return [];
        }
        
        $categoryBudgets = [];
        
        foreach ($categoryLimits as $category => $limit) {
            $spent = abs($spendingByCategory[$category] ?? 0);
            $remaining = $limit - $spent;
            $percentage = $limit > 0 ? ($spent / $limit) * 100 : 0;
            
            $categoryBudgets[] = [
                'category' => $category,
                'limit' => (float) $limit,
                'spent' => (float) $spent,
                'remaining' => (float) $remaining,
                'percentage' => min((float) $percentage, 100),
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
        $budget = $this->getCurrentBudget();
        $categoryBudgets = $this->getCategoryBudgets();
        $summary = $this->getIncomeExpensesSummary();
        
        // Category budget alerts
        foreach ($categoryBudgets as $categoryBudget) {
            if ($categoryBudget['is_over_budget']) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => "You've exceeded your {$categoryBudget['category']} budget by $" . number_format($categoryBudget['spent'] - $categoryBudget['limit'], 2),
                ];
            } elseif ($categoryBudget['percentage'] >= 90) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "You've used " . number_format($categoryBudget['percentage'], 1) . "% of your {$categoryBudget['category']} budget",
                ];
            }
        }
        
        // Overall budget alerts
        if ($budget) {
            if ($budget->remaining_budget < 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => 'You have overspent your budget by $' . number_format(abs($budget->remaining_budget), 2),
                ];
            } elseif ($budget->remaining_budget < ($budget->total_income * 0.1)) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'Low remaining budget: $' . number_format($budget->remaining_budget, 2),
                ];
            }
            
            if ($budget->savings_goal && $budget->savings_actual < ($budget->savings_goal * 0.5)) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'You are behind on your savings goal',
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get spending trend (last 6 months)
     */
    protected function getSpendingTrend(): array
    {
        $currentMonth = Carbon::createFromFormat('Y-m', $this->selectedYear . '-' . $this->selectedMonth);
        $previousMonth = $currentMonth->copy()->subMonth();
        
        // Current month transactions
        $currentTransactions = Transaction::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth()])
            ->get();
        
        // Previous month transactions
        $previousTransactions = Transaction::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$previousMonth->copy()->startOfMonth(), $previousMonth->copy()->endOfMonth()])
            ->get();
        
        $currentExpenses = abs($currentTransactions->where('amount', '<', 0)->sum('amount'));
        $previousExpenses = abs($previousTransactions->where('amount', '<', 0)->sum('amount'));
        
        $change = $currentExpenses - $previousExpenses;
        $changePercentage = $previousExpenses > 0 ? ($change / $previousExpenses) * 100 : 0;
        
        // Get last 6 months data for chart
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $currentMonth->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthTransactions = Transaction::where('user_id', auth()->id())
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->get();
            
            $income = (float) $monthTransactions->where('amount', '>', 0)->sum('amount');
            $expenses = abs((float) $monthTransactions->where('amount', '<', 0)->sum('amount'));
            
            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'income' => $income,
                'expenses' => $expenses,
            ];
        }
        
        return [
            'current_expenses' => (float) $currentExpenses,
            'previous_expenses' => (float) $previousExpenses,
            'change' => (float) $change,
            'change_percentage' => (float) $changePercentage,
            'monthly_data' => $monthlyData,
        ];
    }
    
    /**
     * Get pay schedule information
     */
    protected function getPayScheduleInfo(): array
    {
        $paySchedule = auth()->user()->activePaySchedule;
        
        if (!$paySchedule) {
            return [
                'exists' => false,
                'next_pay_date' => null,
                'days_until' => null,
                'net_pay' => null,
            ];
        }
        
        $nextPayDate = $paySchedule->next_pay_date ?? $paySchedule->calculateNextPayDate();
        $daysUntil = now()->diffInDays($nextPayDate, false);
        
        return [
            'exists' => true,
            'next_pay_date' => $nextPayDate,
            'days_until' => $daysUntil,
            'net_pay' => (float) ($paySchedule->net_pay ?? 0),
            'formatted_net_pay' => $paySchedule->formatted_net_pay ?? 'Not set',
        ];
    }
    
    /**
     * Get bills due before next payday
     */
    protected function getBillsDueBeforePayday()
    {
        $paySchedule = auth()->user()->activePaySchedule;
        
        if (!$paySchedule) {
            return collect();
        }
        
        $nextPayDate = $paySchedule->next_pay_date ?? $paySchedule->calculateNextPayDate();
        
        return Bill::where('user_id', auth()->id())
            ->where(function ($query) {
                $query->whereIn('payment_status', ['upcoming', 'due', 'overdue'])
                      ->orWhereNull('payment_status');
            })
            ->where('next_due_date', '<=', $nextPayDate)
            ->where('next_due_date', '>=', now()->toDateString())
            ->orderBy('next_due_date')
            ->get();
    }
    
    /**
     * Get upcoming bills for current month
     */
    protected function getUpcomingBills()
    {
        $startDate = Carbon::createFromFormat('Y-m', $this->selectedYear . '-' . $this->selectedMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        return Bill::where('user_id', auth()->id())
            ->whereBetween('next_due_date', [$startDate, $endDate])
            ->whereIn('payment_status', ['upcoming', 'due', 'overdue'])
            ->orderBy('next_due_date')
            ->get();
    }
    
    /**
     * Get budget overview data for donut chart
     */
    protected function getBudgetOverview(): array
    {
        $budget = $this->getCurrentBudget();
        
        if (!$budget) {
            return [
                'income' => 0,
                'bills' => 0,
                'spending' => 0,
                'remaining' => 0,
            ];
        }
        
        return [
            'income' => (float) $budget->total_income,
            'bills' => (float) $budget->bills_total,
            'spending' => (float) $budget->spending_total,
            'remaining' => (float) max(0, $budget->remaining_budget),
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
        
        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
            $years[] = (string) $i;
        }
        
        return $years;
    }
    
    /**
     * Open budget edit modal
     */
    public function openEditModal(): void
    {
        $budget = $this->getCurrentBudget();
        
        if ($budget) {
            $this->editingBudgetId = $budget->id;
            $this->expectedIncome = $budget->expected_income;
            $this->savingsGoal = $budget->savings_goal;
            $this->spendingLimit = $budget->spending_limit;
            $this->categoryLimits = $budget->metadata['category_limits'] ?? [];
            $this->autoCalculate = empty($this->categoryLimits);
        } else {
            // Initialize with current spending by category
            $spendingByCategory = $this->getSpendingByCategory();
            $this->categoryLimits = [];
            foreach ($spendingByCategory as $category => $amount) {
                $this->categoryLimits[$category] = $amount * 1.2; // Suggest 20% more than current spending
            }
            $this->autoCalculate = true;
        }
        
        $this->showEditModal = true;
    }
    
    /**
     * Close budget edit modal
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingBudgetId = null;
        $this->categoryLimits = [];
        $this->expectedIncome = null;
        $this->savingsGoal = null;
        $this->spendingLimit = null;
        $this->autoCalculate = true;
    }
    
    /**
     * Save budget
     */
    public function saveBudget(): void
    {
        $budget = $this->getCurrentBudget();
        $month = $this->selectedYear . '-' . $this->selectedMonth;
        
        if (!$budget) {
            $budget = $this->budgetService->getOrCreateBudget(auth()->user(), $month);
        }
        
        // Update budget with manual overrides
        $metadata = $budget->metadata ?? [];
        
        if (!$this->autoCalculate) {
            $metadata['category_limits'] = array_filter($this->categoryLimits, function($limit) {
                return $limit > 0;
            });
        } else {
            // Clear category limits if auto-calculating
            unset($metadata['category_limits']);
        }
        
        $budget->update([
            'expected_income' => $this->expectedIncome,
            'savings_goal' => $this->savingsGoal,
            'spending_limit' => $this->spendingLimit,
            'metadata' => $metadata,
        ]);
        
        // Recalculate budget if auto-calculate is enabled
        if ($this->autoCalculate) {
            $budget = $this->budgetService->calculateBudget(auth()->user(), $month);
        }
        
        $this->closeEditModal();
        $this->dispatch('budget-updated');
    }
    
    /**
     * Update category limit
     */
    public function updateCategoryLimit(string $category, float $limit): void
    {
        $this->categoryLimits[$category] = $limit;
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        $budget = $this->getCurrentBudget();
        $billsDueBeforePayday = $this->getBillsDueBeforePayday();
        $totalBillsDue = $billsDueBeforePayday->sum(function ($bill) {
            return abs((float) $bill->amount);
        });
        
        return view('livewire.budget.budget-dashboard', [
            'currentBudget' => $budget,
            'incomeExpensesSummary' => $this->getIncomeExpensesSummary(),
            'spendingByCategory' => $this->getSpendingByCategory(),
            'categoryBudgets' => $this->getCategoryBudgets(),
            'budgetAlerts' => $this->getBudgetAlerts(),
            'spendingTrend' => $this->getSpendingTrend(),
            'payScheduleInfo' => $this->getPayScheduleInfo(),
            'billsDueBeforePayday' => $billsDueBeforePayday,
            'totalBillsDue' => $totalBillsDue,
            'upcomingBills' => $this->getUpcomingBills(),
            'budgetOverview' => $this->getBudgetOverview(),
            'availableMonths' => $this->getAvailableMonths(),
            'availableYears' => $this->getAvailableYears(),
        ]);
    }
}


