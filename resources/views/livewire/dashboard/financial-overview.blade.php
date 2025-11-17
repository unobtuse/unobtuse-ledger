<div class="space-y-8">
    {{-- Header with Refresh --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-card-foreground">Financial Overview</h1>
            <p class="text-muted-foreground mt-1">Your complete financial dashboard at a glance</p>
        </div>
        <button wire:click="refreshData" class="px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] hover:opacity-90 transition-all duration-150 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
        </button>
    </div>

    {{-- Section 1: Financial Snapshot Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Net Worth Card --}}
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-muted-foreground">Net Worth</h3>
                <div class="w-10 h-10 bg-chart-3/10 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-chart-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-card-foreground mb-2">
                ${{ number_format($netWorth['total'], 2) }}
            </div>
            <div class="flex items-center gap-2">
                @if($netWorth['trend'] === 'up')
                    <svg class="w-4 h-4 text-chart-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-chart-2">+{{ $netWorth['change_percent'] }}%</span>
                @else
                    <svg class="w-4 h-4 text-destructive" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-destructive">{{ $netWorth['change_percent'] }}%</span>
                @endif
                <span class="text-sm text-muted-foreground">vs last month</span>
            </div>
        </div>

        {{-- Cash Flow Card --}}
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-muted-foreground">This Month</h3>
                <div class="w-10 h-10 bg-chart-2/10 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-chart-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold mb-2 {{ $cashFlow['net'] >= 0 ? 'text-chart-2' : 'text-destructive' }}">
                ${{ number_format($cashFlow['net'], 2) }}
            </div>
            <div class="text-xs text-muted-foreground space-y-1">
                <div class="flex justify-between">
                    <span>Income:</span>
                    <span class="font-medium text-card-foreground">${{ number_format($cashFlow['income'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Expenses:</span>
                    <span class="font-medium text-card-foreground">${{ number_format($cashFlow['expenses'], 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Available Cash Card --}}
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-muted-foreground">Available Cash</h3>
                <div class="w-10 h-10 bg-chart-4/10 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-chart-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-card-foreground mb-2">
                ${{ number_format($availableCash['total'], 2) }}
            </div>
            <p class="text-sm text-muted-foreground">Checking + Savings</p>
        </div>

        {{-- Next Payday Card --}}
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-muted-foreground">Next Payday</h3>
                <div class="w-10 h-10 bg-chart-3/10 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-chart-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            @if($nextPayday['has_schedule'])
                <div class="text-3xl font-bold text-card-foreground mb-2">
                    {{ $nextPayday['days_until'] }} days
                </div>
                <div class="text-sm text-muted-foreground">
                    {{ $nextPayday['formatted_date'] }} - {{ $nextPayday['formatted_amount'] }}
                </div>
            @else
                <div class="text-sm text-muted-foreground">
                    No pay schedule set
                </div>
            @endif
        </div>
    </div>

    {{-- Section 6: Financial Health Score (Prominent Display) --}}
    <div class="bg-gradient-to-br from-chart-1 to-chart-4 dark:from-chart-3 dark:to-chart-5 rounded-lg shadow-lg p-8 text-white">
        <div class="flex flex-col md:flex-row items-center gap-8">
            <div class="flex-1">
                <h2 class="text-2xl font-bold mb-2">Financial Health Score</h2>
                <p class="text-primary-foreground/80 mb-4">A composite measure of your financial wellbeing</p>

                <div class="flex items-center gap-4 mb-6">
                    <div class="text-6xl font-bold">{{ $financialHealthScore['total_score'] }}</div>
                    <div>
                        <div class="text-3xl font-bold">{{ $financialHealthScore['grade'] }}</div>
                        <div class="text-sm text-primary-foreground/80">
                            @if($financialHealthScore['trend']['direction'] === 'up')
                                ↑ +{{ $financialHealthScore['trend']['change'] }} from last month
                            @elseif($financialHealthScore['trend']['direction'] === 'down')
                                ↓ {{ $financialHealthScore['trend']['change'] }} from last month
                            @else
                                → No change from last month
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($financialHealthScore['components'] as $key => $component)
                        <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                            <div class="text-xs text-primary-foreground/80 mb-1">{{ $component['description'] }}</div>
                            <div class="text-lg font-bold">{{ $component['score'] }}</div>
                            <div class="text-xs text-primary-foreground/80">{{ $component['formatted_value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="w-64 h-64">
                <x-charts.gauge
                    :id="'health-score-gauge'"
                    :value="$financialHealthScore['total_score']"
                    :max="100"
                    :label="$financialHealthScore['status']"
                    :height="'250px'"
                />
            </div>
        </div>
    </div>

    {{-- Section 2: Spending Intelligence --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Spending Trend Chart --}}
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Income vs Expenses (12 Months)</h3>
            <x-charts.line
                :id="'spending-trend-chart'"
                :labels="$spendingTrend['labels']"
                :datasets="$spendingTrend['datasets']"
                :height="'300px'"
            />
        </div>

        {{-- Top 5 Categories --}}
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Top 5 Spending Categories</h3>
            <div class="space-y-4">
                @foreach($topCategories as $category)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-card-foreground">{{ $category['category'] }}</span>
                            <span class="font-medium text-card-foreground">{{ $category['formatted_amount'] }}</span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            <div class="bg-chart-1 rounded-full h-2" style="width: {{ $category['percentage'] }}%"></div>
                        </div>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ $category['percentage'] }}% of total spending
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Section 3: Bills & Obligations --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Bills Due This Week --}}
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Bills Due This Week</h3>
            @if(count($billsDueThisWeek) > 0)
                <div class="space-y-3">
                    @foreach($billsDueThisWeek as $bill)
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex-1">
                                <div class="font-medium text-card-foreground">{{ $bill['name'] }}</div>
                                <div class="text-sm text-muted-foreground">
                                    Due {{ $bill['formatted_due_date'] }}
                                    @if($bill['is_overdue'])
                                        <span class="text-destructive font-medium">• Overdue</span>
                                    @elseif($bill['is_due_soon'])
                                        <span class="text-chart-3 font-medium">• Due Soon</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-card-foreground">{{ $bill['formatted_amount'] }}</div>
                                <div class="text-xs text-muted-foreground">{{ ucfirst($bill['category']) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted-foreground text-center py-8">No bills due this week</p>
            @endif
        </div>

        {{-- Bills Due Before Payday --}}
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Bills Due Before Next Payday</h3>
            @if(isset($billsDueBeforePayday['bills']) && count($billsDueBeforePayday['bills']) > 0)
                <div class="bg-chart-1/10 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-muted-foreground">Total Due</div>
                            <div class="text-xl font-bold text-card-foreground">{{ $billsDueBeforePayday['formatted_total_due'] }}</div>
                        </div>
                        <div>
                            <div class="text-muted-foreground">Available After Bills</div>
                            <div class="text-xl font-bold text-chart-2">{{ $billsDueBeforePayday['formatted_available_after_bills'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    @foreach($billsDueBeforePayday['bills'] as $bill)
                        <div class="flex justify-between text-sm p-2 hover:bg-muted rounded">
                            <span class="text-card-foreground">{{ $bill['name'] }}</span>
                            <span class="font-medium text-card-foreground">{{ $bill['formatted_amount'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted-foreground text-center py-8">No bills due before next payday</p>
            @endif
        </div>
    </div>

    {{-- Section 4: Account Overview --}}
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <h3 class="text-lg font-semibold text-card-foreground mb-6">Account Overview</h3>

        {{-- Credit Utilization Summary --}}
        @if(count($creditUtilization['cards']) > 0)
            <div class="bg-gradient-to-r from-chart-4/10 to-chart-1/10 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-muted-foreground">Total Credit Utilization</div>
                        <div class="text-2xl font-bold text-card-foreground">{{ $creditUtilization['total_utilization'] }}%</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-muted-foreground">Available Credit</div>
                        <div class="text-xl font-semibold text-card-foreground">{{ $creditUtilization['formatted_total_available'] }}</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Account Cards with Sparklines --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($accountsWithSparklines as $account)
                <div class="border border-border rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="font-medium text-card-foreground">{{ $account['name'] }}</div>
                            <div class="text-xs text-muted-foreground">{{ ucfirst(str_replace('_', ' ', $account['type'])) }}</div>
                        </div>
                        @if($account['needs_sync'])
                            <span class="text-xs px-2 py-1 bg-chart-3/20 text-chart-3 rounded">Needs Sync</span>
                        @endif
                    </div>

                    <div class="text-2xl font-bold text-card-foreground mb-2">
                        {{ $account['formatted_balance'] }}
                    </div>

                    @if($account['is_credit_card'] && $account['utilization'] !== null)
                        <div class="mb-3">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-muted-foreground">Utilization</span>
                                <span class="font-medium">{{ $account['utilization'] }}%</span>
                            </div>
                            <div class="w-full bg-muted rounded-full h-1.5">
                                <div class="rounded-full h-1.5 {{ $account['utilization'] >= 80 ? 'bg-destructive' : ($account['utilization'] >= 50 ? 'bg-chart-3' : 'bg-chart-2') }}"
                                     style="width: {{ min($account['utilization'], 100) }}%"></div>
                            </div>
                        </div>
                    @endif

                    <div class="h-12 -mx-2">
                        <x-charts.sparkline
                            :id="'account-sparkline-' . $account['id']"
                            :data="$account['trend_data']"
                            :labels="$account['trend_labels']"
                            :height="'48px'"
                        />
                    </div>

                    <div class="text-xs text-muted-foreground mt-2">
                        Last synced: {{ $account['last_synced'] ?? 'Never' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section 5: Budget Status --}}
    @if($currentBudget)
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-card-foreground">Budget Progress - {{ now()->format('F Y') }}</h3>
                <a href="{{ route('budget.index') }}" class="text-sm text-primary hover:underline">View Details →</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-muted rounded-lg p-4">
                    <div class="text-sm text-muted-foreground">Total Income</div>
                    <div class="text-2xl font-bold text-card-foreground">${{ number_format($currentBudget->total_income, 2) }}</div>
                </div>
                <div class="bg-muted rounded-lg p-4">
                    <div class="text-sm text-muted-foreground">Total Expenses</div>
                    <div class="text-2xl font-bold text-card-foreground">${{ number_format($currentBudget->spending_total, 2) }}</div>
                </div>
                <div class="bg-muted rounded-lg p-4">
                    <div class="text-sm text-muted-foreground">Remaining</div>
                    <div class="text-2xl font-bold {{ $currentBudget->remaining_budget >= 0 ? 'text-chart-2' : 'text-destructive' }}">
                        ${{ number_format($currentBudget->remaining_budget, 2) }}
                    </div>
                </div>
            </div>

            @if($currentBudget->category_breakdown && count($currentBudget->category_breakdown) > 0)
                <div class="space-y-3">
                    <h4 class="text-sm font-medium text-card-foreground">Top Categories</h4>
                    @php
                        $sortedCategories = collect($currentBudget->category_breakdown)->sortByDesc(fn($amount) => $amount)->take(6);
                    @endphp
                    @foreach($sortedCategories as $category => $amount)
                        @php
                            $limit = $currentBudget->metadata['category_limits'][$category] ?? null;
                            $percentage = $limit ? min(($amount / $limit) * 100, 100) : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-card-foreground">{{ ucfirst($category) }}</span>
                                <span class="text-card-foreground">
                                    ${{ number_format($amount, 2) }}
                                    @if($limit)
                                        <span class="text-muted-foreground">/ ${{ number_format($limit, 2) }}</span>
                                    @endif
                                </span>
                            </div>
                            @if($limit)
                                <div class="w-full bg-muted rounded-full h-2">
                                    <div class="rounded-full h-2 {{ $percentage >= 100 ? 'bg-destructive' : ($percentage >= 80 ? 'bg-chart-3' : 'bg-chart-2') }}"
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Section 7: Recent Activity --}}
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-card-foreground">Recent Transactions</h3>
            <a href="{{ route('transactions.index') }}" class="text-sm text-primary hover:underline">View All →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-border">
                        <th class="text-left py-2 text-sm font-medium text-muted-foreground">Date</th>
                        <th class="text-left py-2 text-sm font-medium text-muted-foreground">Merchant</th>
                        <th class="text-left py-2 text-sm font-medium text-muted-foreground">Category</th>
                        <th class="text-left py-2 text-sm font-medium text-muted-foreground">Account</th>
                        <th class="text-right py-2 text-sm font-medium text-muted-foreground">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTransactions as $transaction)
                        <tr class="border-b border-border hover:bg-muted">
                            <td class="py-3 text-sm text-muted-foreground">{{ $transaction['formatted_date'] }}</td>
                            <td class="py-3 text-sm text-card-foreground">{{ $transaction['merchant'] }}</td>
                            <td class="py-3 text-sm text-muted-foreground">
                                <span class="px-2 py-1 bg-muted rounded text-xs">
                                    {{ $transaction['category'] }}
                                </span>
                            </td>
                            <td class="py-3 text-sm text-muted-foreground">{{ $transaction['account'] }}</td>
                            <td class="py-3 text-sm text-right font-medium {{ $transaction['is_credit'] ? 'text-chart-2' : 'text-card-foreground' }}">
                                {{ $transaction['is_credit'] ? '+' : '' }}{{ $transaction['formatted_amount'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
