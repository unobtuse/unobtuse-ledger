<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-card-foreground">Account Analytics</h2>
            <p class="text-sm text-muted-foreground mt-1">Comprehensive insights into your accounts</p>
        </div>
    </div>

    <!-- Net Worth Trend Chart -->
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <h3 class="text-lg font-semibold text-card-foreground mb-4">Net Worth Trend (12 Months)</h3>
        <x-charts.line
            id="net-worth-trend"
            height="350px"
            :labels="$netWorthTrend['labels']"
            :data="$netWorthTrend['data']"
        />
    </div>

    <!-- Two Column Layout: Account Balance Trends & Type Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Account Balance Trends (Multi-line) -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Account Balance Trends (30 Days)</h3>
            @php
                $trendDatasets = [];
                $colors = ['chart-1', 'chart-2', 'chart-3', 'chart-4', 'chart-5', 'chart-6'];
                foreach ($accountBalanceTrends as $index => $accountTrend) {
                    $trendDatasets[] = [
                        'label' => $accountTrend['account_name'],
                        'data' => $accountTrend['trend'],
                        'borderColor' => null, // Will be set by component
                        'backgroundColor' => null,
                    ];
                }
            @endphp
            @if(!empty($accountBalanceTrends))
                <x-charts.line
                    id="account-balance-trends"
                    height="300px"
                    :labels="$accountBalanceTrends[0]['labels'] ?? []"
                    :datasets="$trendDatasets"
                />
            @else
                <div class="flex items-center justify-center h-[300px] text-muted-foreground">
                    <p>No account balance data available</p>
                </div>
            @endif
        </div>

        <!-- Account Type Distribution -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Account Type Distribution</h3>
            <x-charts.doughnut
                id="account-type-distribution"
                height="300px"
                :labels="$accountTypeDistribution['labels']"
                :data="$accountTypeDistribution['data']"
            />
            <div class="mt-4 space-y-2">
                @foreach($accountTypeDistribution['labels'] as $index => $label)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ $label }}</span>
                        <span class="font-medium text-card-foreground">
                            {{ $accountTypeDistribution['counts'][$index] }} account(s)
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Credit Utilization Section -->
    @if(!empty($creditUtilization['cards']))
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Credit Card Utilization</h3>
            
            <!-- Summary -->
            <div class="mb-6 p-4 bg-muted rounded-[var(--radius-md)]">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-muted-foreground">Total Utilization</p>
                        <p class="text-2xl font-semibold text-card-foreground">
                            {{ number_format($creditUtilization['total_utilization'], 1) }}%
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total Balance</p>
                        <p class="text-2xl font-semibold text-card-foreground">
                            {{ $creditUtilization['formatted_total_balance'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total Available</p>
                        <p class="text-2xl font-semibold text-card-foreground">
                            {{ $creditUtilization['formatted_total_available'] }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Individual Card Gauges -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($creditUtilization['cards'] as $card)
                    <div class="bg-background border border-border rounded-[var(--radius-md)] p-4">
                        <h4 class="font-medium text-card-foreground mb-2">{{ $card['name'] }}</h4>
                        <x-charts.gauge
                            id="credit-gauge-{{ $card['id'] }}"
                            height="150px"
                            :value="$card['utilization']"
                            :max="100"
                            :label="number_format($card['utilization'], 1) . '%'"
                            :colors="[
                                '#ef4444', // red for high
                                '#f59e0b', // amber for moderate
                                '#10b981'  // green for good
                            ]"
                            :thresholds="[30, 50]"
                        />
                        <div class="mt-3 space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Balance:</span>
                                <span class="font-medium text-card-foreground">{{ $card['formatted_balance'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Limit:</span>
                                <span class="font-medium text-card-foreground">{{ $card['formatted_limit'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Available:</span>
                                <span class="font-medium text-card-foreground">{{ $card['formatted_available'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Transaction Volume & Account Health -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Transaction Volume by Account -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Transaction Volume (Last 30 Days)</h3>
            <x-charts.bar
                id="transaction-volume"
                height="300px"
                :labels="$transactionVolume['labels']"
                :data="$transactionVolume['data']"
                horizontal="true"
            />
        </div>

        <!-- Accounts Needing Attention -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Accounts Needing Attention</h3>
            @if(empty($accountsNeedingAttention))
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-4 text-sm text-muted-foreground">All accounts are in good standing!</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($accountsNeedingAttention as $account)
                        <div class="bg-background border border-border rounded-[var(--radius-md)] p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-card-foreground">{{ $account['name'] }}</h4>
                                    <p class="text-xs text-muted-foreground mt-1 capitalize">{{ str_replace('_', ' ', $account['type']) }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium bg-warning/20 text-warning rounded-[var(--radius-sm)]">
                                    Action Needed
                                </span>
                            </div>
                            <div class="mt-3 space-y-1">
                                @foreach($account['issues'] as $issue)
                                    <div class="flex items-center text-sm text-muted-foreground">
                                        <svg class="w-4 h-4 mr-2 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        {{ $issue }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

