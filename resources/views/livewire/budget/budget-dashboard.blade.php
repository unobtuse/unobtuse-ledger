<div class="space-y-6">
    <!-- Month/Year Selector -->
    <div class="bg-card border border-border rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-4">
            <select wire:model.live="selectedMonth" 
                    class="px-4 py-2 bg-background border border-input rounded-lg text-foreground">
                @foreach($availableMonths as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="selectedYear" 
                    class="px-4 py-2 bg-background border border-input rounded-lg text-foreground">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Budget Alerts -->
    @if(count($budgetAlerts) > 0)
        <div class="space-y-3">
            @foreach($budgetAlerts as $alert)
                <div class="p-4 rounded-lg border {{ $alert['type'] === 'danger' ? 'bg-destructive/10 border-destructive/30' : 'bg-chart-4/10 border-chart-4/30' }}">
                    <p class="text-sm {{ $alert['type'] === 'danger' ? 'text-destructive' : 'text-chart-4' }}">
                        {{ $alert['message'] }}
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Income/Expenses Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Income</p>
            <p class="text-2xl font-semibold text-chart-2 mt-1">${{ number_format($incomeExpensesSummary['total_income'], 2) }}</p>
        </div>
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Expenses</p>
            <p class="text-2xl font-semibold text-destructive mt-1">${{ number_format($incomeExpensesSummary['total_expenses'], 2) }}</p>
        </div>
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Net Change</p>
            <p class="text-2xl font-semibold {{ $incomeExpensesSummary['net_change'] >= 0 ? 'text-chart-2' : 'text-destructive' }} mt-1">
                {{ $incomeExpensesSummary['net_change'] >= 0 ? '+' : '' }}${{ number_format($incomeExpensesSummary['net_change'], 2) }}
            </p>
        </div>
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Savings Rate</p>
            <p class="text-2xl font-semibold text-card-foreground mt-1">{{ number_format($incomeExpensesSummary['savings_rate'], 1) }}%</p>
        </div>
    </div>

    <!-- Spending Trend -->
    <div class="bg-card border border-border rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-card-foreground mb-4">Spending Trend</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-muted-foreground">Current Month</p>
                <p class="text-xl font-semibold text-card-foreground">${{ number_format($spendingTrend['current_expenses'], 2) }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-muted-foreground">Change</p>
                <p class="text-xl font-semibold {{ $spendingTrend['change'] <= 0 ? 'text-chart-2' : 'text-destructive' }}">
                    {{ $spendingTrend['change'] >= 0 ? '+' : '' }}${{ number_format(abs($spendingTrend['change']), 2) }}
                    ({{ $spendingTrend['change'] >= 0 ? '+' : '' }}{{ number_format($spendingTrend['change_percentage'], 1) }}%)
                </p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Previous Month</p>
                <p class="text-xl font-semibold text-card-foreground">${{ number_format($spendingTrend['previous_expenses'], 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Spending by Category -->
    <div class="bg-card border border-border rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-card-foreground mb-4">Spending by Category</h3>
        @if(count($spendingByCategory) > 0)
            <div class="space-y-3">
                @foreach($spendingByCategory as $category => $amount)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-card-foreground">{{ $category }}</span>
                        <span class="text-sm font-semibold text-card-foreground">${{ number_format($amount, 2) }}</span>
                    </div>
                    <div class="w-full bg-muted rounded-full h-2">
                        <div class="bg-chart-1 h-2 rounded-full" 
                             style="width: {{ min(($amount / max(array_values($spendingByCategory))) * 100, 100) }}%"></div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-center text-muted-foreground py-8">No spending data for this period</p>
        @endif
    </div>

    <!-- Category Budgets -->
    @if(count($categoryBudgets) > 0)
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Budget Progress</h3>
            <div class="space-y-4">
                @foreach($categoryBudgets as $budget)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-card-foreground">{{ $budget['category'] }}</span>
                            <span class="text-sm text-muted-foreground">
                                ${{ number_format($budget['spent'], 2) }} / ${{ number_format($budget['limit'], 2) }}
                            </span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-3">
                            <div class="{{ $budget['is_over_budget'] ? 'bg-destructive' : 'bg-chart-2' }} h-3 rounded-full transition-all" 
                                 style="width: {{ min($budget['percentage'], 100) }}%"></div>
                        </div>
                        @if($budget['is_over_budget'])
                            <p class="text-xs text-destructive mt-1">Over budget by ${{ number_format($budget['spent'] - $budget['limit'], 2) }}</p>
                        @else
                            <p class="text-xs text-muted-foreground mt-1">${{ number_format($budget['remaining'], 2) }} remaining</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-card border border-border rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-card-foreground">No Budget Set</h3>
            <p class="mt-2 text-sm text-muted-foreground">Create a budget to track your spending and savings goals.</p>
        </div>
    @endif
</div>


