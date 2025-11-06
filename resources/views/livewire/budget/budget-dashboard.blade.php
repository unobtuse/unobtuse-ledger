<div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <select wire:model.live="selectedMonth" 
                        class="px-4 py-2 bg-background border border-input rounded-lg text-foreground text-sm font-medium focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                    @foreach($availableMonths as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select wire:model.live="selectedYear" 
                        class="px-4 py-2 bg-background border border-input rounded-lg text-foreground text-sm font-medium focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
                
                @if($currentBudget)
                    <span class="px-3 py-1 text-xs font-medium rounded-full {{ $currentBudget->status === 'overspent' ? 'bg-destructive/20 text-destructive' : ($currentBudget->status === 'active' ? 'bg-chart-2/20 text-chart-2' : 'bg-muted text-muted-foreground') }}">
                        {{ ucfirst($currentBudget->status) }}
                    </span>
                @endif
            </div>
            
            <button wire:click="openEditModal" 
                    class="px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:opacity-90 transition-all duration-150 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Budget
            </button>
        </div>
    </div>

    <!-- Budget Alerts -->
    @if(count($budgetAlerts) > 0)
        <div class="space-y-3">
            @foreach($budgetAlerts as $alert)
                <div class="p-4 rounded-[var(--radius-default)] border {{ $alert['type'] === 'danger' ? 'bg-destructive/10 border-destructive/30' : 'bg-chart-4/10 border-chart-4/30' }}">
                    <div class="flex items-center gap-2">
                        @if($alert['type'] === 'danger')
                            <svg class="w-5 h-5 text-destructive flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-chart-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        @endif
                        <p class="text-sm {{ $alert['type'] === 'danger' ? 'text-destructive' : 'text-chart-4' }}">
                            {{ $alert['message'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Income -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-muted-foreground">Total Income</p>
                <svg class="w-5 h-5 text-chart-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
            <p class="text-2xl font-semibold text-chart-2">${{ number_format($incomeExpensesSummary['total_income'], 2) }}</p>
            @if($currentBudget && $currentBudget->expected_income)
                <p class="text-xs text-muted-foreground mt-1">
                    Expected: ${{ number_format($currentBudget->expected_income, 2) }}
                </p>
            @endif
        </div>

        <!-- Total Expenses -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-muted-foreground">Total Expenses</p>
                <svg class="w-5 h-5 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
            </div>
            <p class="text-2xl font-semibold text-destructive">${{ number_format($incomeExpensesSummary['total_expenses'], 2) }}</p>
            @if($spendingTrend['change'] != 0)
                <p class="text-xs {{ $spendingTrend['change'] < 0 ? 'text-chart-2' : 'text-destructive' }} mt-1">
                    {{ $spendingTrend['change'] >= 0 ? '+' : '' }}{{ number_format($spendingTrend['change_percentage'], 1) }}% from last month
                </p>
            @endif
        </div>

        <!-- Remaining Budget -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-muted-foreground">Remaining Budget</p>
                @if($incomeExpensesSummary['remaining_budget'] >= 0)
                    <svg class="w-5 h-5 text-chart-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @else
                    <svg class="w-5 h-5 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                @endif
            </div>
            <p class="text-2xl font-semibold {{ $incomeExpensesSummary['remaining_budget'] >= 0 ? 'text-chart-2' : 'text-destructive' }}">
                {{ $incomeExpensesSummary['remaining_budget'] >= 0 ? '+' : '' }}${{ number_format($incomeExpensesSummary['remaining_budget'], 2) }}
            </p>
            @if($currentBudget && $currentBudget->available_to_spend)
                <p class="text-xs text-muted-foreground mt-1">
                    Available: ${{ number_format($currentBudget->available_to_spend, 2) }}
                </p>
            @endif
        </div>

        <!-- Savings Rate -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-muted-foreground">Savings Rate</p>
                <svg class="w-5 h-5 text-card-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <p class="text-2xl font-semibold text-card-foreground">{{ number_format($incomeExpensesSummary['savings_rate'], 1) }}%</p>
            @if($currentBudget && $currentBudget->savings_goal)
                <p class="text-xs text-muted-foreground mt-1">
                    Goal: ${{ number_format($currentBudget->savings_goal, 2) }}
                </p>
            @endif
        </div>
    </div>

    <!-- Pay Schedule Integration & Bills Due Before Payday -->
    @if($payScheduleInfo['exists'])
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-card-foreground mb-1">Pay Schedule</h3>
                    <p class="text-sm text-muted-foreground">
                        Next payday: <span class="font-medium text-card-foreground">{{ $payScheduleInfo['next_pay_date']->format('M d, Y') }}</span>
                        <span class="ml-2 px-2 py-0.5 bg-primary/20 text-primary rounded-full text-xs font-medium">
                            {{ $payScheduleInfo['days_until'] }} {{ $payScheduleInfo['days_until'] === 1 ? 'day' : 'days' }}
                        </span>
                    </p>
                </div>
                @if($payScheduleInfo['net_pay'])
                    <div class="text-right">
                        <p class="text-sm text-muted-foreground">Net Pay</p>
                        <p class="text-xl font-semibold text-card-foreground">{{ $payScheduleInfo['formatted_net_pay'] }}</p>
                    </div>
                @endif
            </div>

            @if($billsDueBeforePayday->count() > 0)
                <div class="border-t border-border pt-4 mt-4">
                    <h4 class="text-sm font-semibold text-card-foreground mb-3">Bills Due Before Payday</h4>
                    <div class="space-y-2">
                        @foreach($billsDueBeforePayday as $bill)
                            <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-card-foreground">{{ $bill->name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Due: {{ $bill->next_due_date->format('M d, Y') }}
                                        ({{ $bill->days_until_due }} {{ $bill->days_until_due === 1 ? 'day' : 'days' }})
                                    </p>
                                </div>
                                <p class="text-sm font-semibold text-card-foreground">${{ number_format(abs((float) $bill->amount), 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-4 border-t border-border flex items-center justify-between">
                        <p class="text-sm font-medium text-card-foreground">Total Due</p>
                        <p class="text-lg font-semibold text-card-foreground">${{ number_format($totalBillsDue, 2) }}</p>
                    </div>
                    @if($payScheduleInfo['net_pay'])
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-sm text-muted-foreground">Available After Bills</p>
                            <p class="text-base font-medium {{ ($payScheduleInfo['net_pay'] - $totalBillsDue) >= 0 ? 'text-chart-2' : 'text-destructive' }}">
                                ${{ number_format($payScheduleInfo['net_pay'] - $totalBillsDue, 2) }}
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <div class="border-t border-border pt-4 mt-4 text-center py-4">
                    <p class="text-sm text-muted-foreground">No bills due before next payday</p>
                </div>
            @endif
        </div>
    @endif

    <!-- Budget Overview & Spending Trend -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Budget Overview Donut Chart -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Budget Overview</h3>
            @if($budgetOverview['income'] > 0)
                <div class="flex items-center justify-center">
                    <div class="relative w-48 h-48">
                        <svg class="transform -rotate-90 w-48 h-48">
                            @php
                                $total = $budgetOverview['income'];
                                if ($total <= 0) {
                                    $total = 1; // Avoid division by zero
                                }
                                $billsPercent = ($budgetOverview['bills'] / $total) * 100;
                                $spendingPercent = ($budgetOverview['spending'] / $total) * 100;
                                $remainingPercent = ($budgetOverview['remaining'] / $total) * 100;
                                
                                $circumference = 2 * pi() * 90;
                                $billsDash = ($billsPercent / 100) * $circumference;
                                $spendingDash = ($spendingPercent / 100) * $circumference;
                                $remainingDash = ($remainingPercent / 100) * $circumference;
                                
                                $billsOffset = 0;
                                $spendingOffset = -$billsDash;
                                $remainingOffset = -($billsDash + $spendingDash);
                            @endphp
                            <circle cx="96" cy="96" r="90" stroke="oklch(0.269 0 0)" stroke-width="12" fill="none" />
                            @if($budgetOverview['bills'] > 0)
                                <circle cx="96" cy="96" r="90" 
                                        stroke="oklch(0.769 0.188 70.08)" 
                                        stroke-width="12" 
                                        fill="none"
                                        stroke-dasharray="{{ $billsDash }} {{ $circumference }}"
                                        stroke-dashoffset="{{ $billsOffset }}"
                                        class="transition-all duration-300" />
                            @endif
                            @if($budgetOverview['spending'] > 0)
                                <circle cx="96" cy="96" r="90" 
                                        stroke="oklch(0.488 0.243 264.376)" 
                                        stroke-width="12" 
                                        fill="none"
                                        stroke-dasharray="{{ $spendingDash }} {{ $circumference }}"
                                        stroke-dashoffset="{{ $spendingOffset }}"
                                        class="transition-all duration-300" />
                            @endif
                            @if($budgetOverview['remaining'] > 0)
                                <circle cx="96" cy="96" r="90" 
                                        stroke="oklch(0.696 0.17 162.48)" 
                                        stroke-width="12" 
                                        fill="none"
                                        stroke-dasharray="{{ $remainingDash }} {{ $circumference }}"
                                        stroke-dashoffset="{{ $remainingOffset }}"
                                        class="transition-all duration-300" />
                            @endif
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-xs text-muted-foreground">Remaining</p>
                                <p class="text-2xl font-semibold text-card-foreground">${{ number_format($budgetOverview['remaining'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded" style="background: oklch(0.769 0.188 70.08);"></div>
                        <div class="flex-1">
                            <p class="text-xs text-muted-foreground">Bills</p>
                            <p class="text-sm font-semibold text-card-foreground">${{ number_format($budgetOverview['bills'], 2) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded" style="background: oklch(0.488 0.243 264.376);"></div>
                        <div class="flex-1">
                            <p class="text-xs text-muted-foreground">Spending</p>
                            <p class="text-sm font-semibold text-card-foreground">${{ number_format($budgetOverview['spending'], 2) }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-sm text-muted-foreground">No budget data available</p>
                </div>
            @endif
        </div>

        <!-- Spending Trend Chart -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Spending Trend (6 Months)</h3>
            @if(count($spendingTrend['monthly_data']) > 0)
                @php
                    $maxIncome = max(array_column($spendingTrend['monthly_data'], 'income'));
                    $maxExpenses = max(array_column($spendingTrend['monthly_data'], 'expenses'));
                    $maxValue = max($maxIncome, $maxExpenses);
                    if ($maxValue <= 0) {
                        $maxValue = 1; // Avoid division by zero
                    }
                @endphp
                <div class="h-64 flex items-end justify-between gap-2">
                    @foreach($spendingTrend['monthly_data'] as $data)
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full flex flex-col justify-end gap-1" style="height: 200px;">
                                @if($maxIncome > 0)
                                    <div class="w-full bg-chart-2 rounded-t transition-all duration-300 hover:opacity-80" 
                                         style="height: {{ ($data['income'] / $maxValue) * 100 }}%;"
                                         title="Income: ${{ number_format($data['income'], 2) }}">
                                    </div>
                                @endif
                                @if($maxExpenses > 0)
                                    <div class="w-full bg-destructive rounded-t transition-all duration-300 hover:opacity-80" 
                                         style="height: {{ ($data['expenses'] / $maxValue) * 100 }}%;"
                                         title="Expenses: ${{ number_format($data['expenses'], 2) }}">
                                    </div>
                                @endif
                            </div>
                            <p class="text-xs text-muted-foreground text-center">{{ $data['month'] }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex items-center justify-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-chart-2 rounded"></div>
                        <p class="text-xs text-muted-foreground">Income</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-destructive rounded"></div>
                        <p class="text-xs text-muted-foreground">Expenses</p>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-sm text-muted-foreground">No spending data available</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Category Budget Progress -->
    @if(count($categoryBudgets) > 0)
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Category Budget Progress</h3>
            <div class="space-y-4">
                @foreach($categoryBudgets as $budget)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-card-foreground">{{ $budget['category'] }}</span>
                            <span class="text-sm text-muted-foreground">
                                ${{ number_format($budget['spent'], 2) }} / ${{ number_format($budget['limit'], 2) }}
                            </span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-3 overflow-hidden">
                            <div class="{{ $budget['is_over_budget'] ? 'bg-destructive' : ($budget['percentage'] >= 90 ? 'bg-chart-4' : 'bg-chart-2') }} h-3 rounded-full transition-all duration-300" 
                                 style="width: {{ min($budget['percentage'], 100) }}%"></div>
                        </div>
                        @if($budget['is_over_budget'])
                            <p class="text-xs text-destructive mt-1">Over budget by ${{ number_format($budget['spent'] - $budget['limit'], 2) }}</p>
                        @else
                            <p class="text-xs text-muted-foreground mt-1">${{ number_format($budget['remaining'], 2) }} remaining ({{ number_format($budget['percentage'], 1) }}%)</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Top Spending Categories -->
    @if(count($spendingByCategory) > 0)
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Top Spending Categories</h3>
            @php
                $topCategories = array_slice($spendingByCategory, 0, 10, true);
                $maxSpending = max($topCategories);
                $totalSpending = array_sum($spendingByCategory);
            @endphp
            <div class="space-y-3">
                @foreach($topCategories as $category => $amount)
                    @php
                        $percentage = $totalSpending > 0 ? ($amount / $totalSpending) * 100 : 0;
                        $barWidth = $maxSpending > 0 ? ($amount / $maxSpending) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-card-foreground">{{ $category }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-muted-foreground">{{ number_format($percentage, 1) }}%</span>
                                <span class="text-sm font-semibold text-card-foreground">${{ number_format($amount, 2) }}</span>
                            </div>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            <div class="bg-chart-1 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $barWidth }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Upcoming Bills -->
    @if($upcomingBills->count() > 0)
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Upcoming Bills This Month</h3>
            <div class="space-y-2">
                @foreach($upcomingBills as $bill)
                    <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg hover:bg-muted transition-colors duration-150">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-card-foreground">{{ $bill->name }}</p>
                            <p class="text-xs text-muted-foreground">
                                Due: {{ $bill->next_due_date->format('M d, Y') }}
                                @if($bill->category)
                                    • {{ $bill->category }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $bill->payment_status === 'overdue' ? 'bg-destructive/20 text-destructive' : ($bill->payment_status === 'due' ? 'bg-chart-4/20 text-chart-4' : 'bg-muted text-muted-foreground') }}">
                                {{ ucfirst($bill->payment_status ?? 'upcoming') }}
                            </span>
                            <p class="text-sm font-semibold text-card-foreground">${{ number_format(abs((float) $bill->amount), 2) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Budget Edit Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" 
             x-show="true"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             wire:click="closeEditModal">
            <div class="bg-card border border-border rounded-[var(--radius-large)] shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                 wire:click.stop
                 x-transition:enter="transition-transform ease-out duration-200"
                 x-transition:enter-start="scale-95 opacity-0"
                 x-transition:enter-end="scale-100 opacity-100">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-card-foreground">Edit Budget</h2>
                        <button wire:click="closeEditModal" class="text-muted-foreground hover:text-card-foreground transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <!-- Auto-calculate Toggle -->
                        <div class="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-card-foreground">Auto-calculate from transactions</p>
                                <p class="text-xs text-muted-foreground mt-1">Automatically calculate budgets based on your spending patterns</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="autoCalculate" class="sr-only peer">
                                <div class="w-11 h-6 bg-muted peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-ring rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>

                        <!-- Expected Income -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Expected Income</label>
                            <input type="number" 
                                   wire:model="expectedIncome" 
                                   step="0.01"
                                   placeholder="0.00"
                                   class="w-full px-4 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        </div>

                        <!-- Savings Goal -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Savings Goal</label>
                            <input type="number" 
                                   wire:model="savingsGoal" 
                                   step="0.01"
                                   placeholder="0.00"
                                   class="w-full px-4 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        </div>

                        <!-- Spending Limit -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Overall Spending Limit</label>
                            <input type="number" 
                                   wire:model="spendingLimit" 
                                   step="0.01"
                                   placeholder="0.00"
                                   class="w-full px-4 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        </div>

                        <!-- Category Limits -->
                        @if(!$autoCalculate)
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Category Budget Limits</label>
                                <div class="space-y-3 max-h-64 overflow-y-auto">
                                    @foreach($spendingByCategory as $category => $amount)
                                        <div class="flex items-center gap-3">
                                            <label class="flex-1 text-sm text-card-foreground">{{ $category }}</label>
                                            <input type="number" 
                                                   wire:model.live="categoryLimits.{{ $category }}" 
                                                   step="0.01"
                                                   placeholder="{{ number_format($amount * 1.2, 2) }}"
                                                   class="w-32 px-3 py-2 bg-background border border-input rounded-lg text-foreground text-sm placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                            <button wire:click="closeEditModal" 
                                    class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground border border-border rounded-lg hover:bg-muted transition-colors duration-150">
                                Cancel
                            </button>
                            <button wire:click="saveBudget" 
                                    class="px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:opacity-90 transition-all duration-150">
                                Save Budget
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
