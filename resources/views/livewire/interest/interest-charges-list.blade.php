<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-card-foreground">Interest Charges</h2>
            <p class="text-sm text-muted-foreground mt-1">Track all interest and finance charges across your credit accounts</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Interest -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Interest</p>
            <p class="text-3xl font-semibold text-destructive mt-1">${{ number_format($summaryStats['total_interest'], 2) }}</p>
            <p class="text-xs text-muted-foreground mt-1">All time</p>
        </div>

        <!-- Charge Count -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Charges</p>
            <p class="text-3xl font-semibold text-card-foreground mt-1">{{ $summaryStats['charge_count'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">Interest transactions</p>
        </div>

        <!-- This Month -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">This Month</p>
            <p class="text-3xl font-semibold text-chart-4 mt-1">${{ number_format($summaryStats['this_month'], 2) }}</p>
            <p class="text-xs text-muted-foreground mt-1">
                @if($summaryStats['last_month'] > 0)
                    @php
                        $change = (($summaryStats['this_month'] - $summaryStats['last_month']) / $summaryStats['last_month']) * 100;
                    @endphp
                    <span class="{{ $change > 0 ? 'text-destructive' : 'text-chart-2' }}">
                        {{ $change > 0 ? '↑' : '↓' }} {{ abs(round($change, 1)) }}% vs last month
                    </span>
                @else
                    Current month
                @endif
            </p>
        </div>

        <!-- Average Charge -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Average Charge</p>
            <p class="text-3xl font-semibold text-card-foreground mt-1">${{ number_format($summaryStats['average_charge'], 2) }}</p>
            <p class="text-xs text-muted-foreground mt-1">Per transaction</p>
        </div>
    </div>

    <!-- Interest by Account -->
    @if($summaryStats['by_account']->isNotEmpty())
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Interest by Account</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($summaryStats['by_account'] as $accountData)
                    <div class="bg-muted/30 rounded-[var(--radius-md)] p-4 cursor-pointer hover:bg-muted/50 transition-colors"
                         wire:click="$set('accountFilter', '{{ $accountData['account']->id }}')">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-card-foreground truncate">
                                    {{ $accountData['account']->institution_name }} - {{ $accountData['account']->display_name_without_mask }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ $accountData['count'] }} charges</p>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-destructive">${{ number_format($accountData['total'], 2) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filters Bar -->
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <div class="flex flex-col gap-4">
            <!-- Row 1: Search and Date Range -->
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search interest charges..."
                           class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                </div>

                <!-- Date Range Preset -->
                <select wire:model.live="dateRange" 
                        class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                    <option value="all">All Time</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="last_3_months">Last 3 Months</option>
                    <option value="last_6_months">Last 6 Months</option>
                    <option value="this_year">This Year</option>
                </select>
            </div>

            <!-- Row 2: Account and Reset -->
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Account Filter -->
                <select wire:model.live="accountFilter" 
                        class="flex-1 px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->display_name }}</option>
                    @endforeach
                </select>

                <!-- Reset Button -->
                <button wire:click="resetFilters" 
                        class="px-4 py-2 bg-muted text-muted-foreground rounded-[var(--radius-sm)] font-medium text-sm hover:bg-muted/80 transition-all duration-150 whitespace-nowrap">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Interest Charges List -->
    @if($interestCharges->isEmpty())
        <!-- Empty State -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-card-foreground">No interest charges found</h3>
            <p class="mt-2 text-sm text-muted-foreground">
                @if($search || $accountFilter || $dateRange !== 'all')
                    Try adjusting your filters to see more results.
                @else
                    Great news! No interest charges detected in your transactions.
                @endif
            </p>
        </div>
    @else
        <!-- Interest Table -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-card-foreground"
                                wire:click="sortBy('transaction_date')">
                                Date
                                @if($sortField === 'transaction_date')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-card-foreground"
                                wire:click="sortBy('name')">
                                Description
                                @if($sortField === 'name')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Account
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-card-foreground"
                                wire:click="sortBy('amount')">
                                Amount
                                @if($sortField === 'amount')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($interestCharges as $charge)
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-card-foreground">
                                    {{ $charge->transaction_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-card-foreground">
                                    <div class="max-w-md">
                                        <p class="font-medium truncate">{{ $charge->merchant_name ?? $charge->name }}</p>
                                        @if($charge->merchant_name && $charge->merchant_name !== $charge->name)
                                            <p class="text-xs text-muted-foreground truncate">{{ $charge->name }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    @if($charge->account)
                                        <div class="space-y-1">
                                            <div class="font-medium">
                                                {{ $charge->account->institution_name }} - {{ $charge->account->display_name_without_mask }}@if($charge->account->mask) - {{ $charge->account->mask }}@endif
                                            </div>
                                            @if($charge->account->credit_limit && $charge->account->credit_limit > 0)
                                                @php
                                                    $utilization = ($charge->account->balance / $charge->account->credit_limit) * 100;
                                                    $utilization = min($utilization, 100);
                                                    
                                                    if ($utilization <= 30) {
                                                        $barColor = 'bg-chart-2';
                                                    } elseif ($utilization <= 70) {
                                                        $barColor = 'bg-chart-4';
                                                    } else {
                                                        $barColor = 'bg-destructive';
                                                    }
                                                @endphp
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                                                        <div class="{{ $barColor }} h-full rounded-full transition-all duration-300" style="width: {{ $utilization }}%"></div>
                                                    </div>
                                                    <span class="text-xs text-muted-foreground whitespace-nowrap">{{ number_format($utilization, 0) }}%</span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        Unknown
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-destructive">
                                    ${{ number_format($charge->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <button wire:click="showDetails('{{ $charge->id }}')"
                                            class="text-primary hover:text-primary/80 font-medium">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-border">
                {{ $interestCharges->links() }}
            </div>
        </div>
    @endif

    <!-- Details Modal -->
    @if($showDetailsModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-data x-show="$wire.showDetailsModal" x-transition>
            <div class="bg-card border border-border rounded-[var(--radius-large)] shadow-elevated w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.away="$wire.closeDetails()">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-card border-b border-border p-6 z-10">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-card-foreground">{{ $selectedTransaction->merchant_name ?? $selectedTransaction->name }}</h3>
                            <p class="text-sm text-muted-foreground mt-1">{{ $selectedTransaction->transaction_date->format('l, F j, Y') }}</p>
                        </div>
                        <button wire:click="closeDetails" class="text-muted-foreground hover:text-card-foreground">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <!-- Amount -->
                    <div class="text-center py-4 bg-destructive/10 rounded-[var(--radius-md)]">
                        <p class="text-sm text-muted-foreground mb-1">Interest Charge</p>
                        <p class="text-4xl font-bold text-destructive">${{ number_format($selectedTransaction->amount, 2) }}</p>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-muted-foreground mb-1">Account</p>
                            <p class="text-sm font-medium text-card-foreground">
                                {{ $selectedTransaction->account->display_name ?? 'Unknown' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted-foreground mb-1">Date</p>
                            <p class="text-sm font-medium text-card-foreground">
                                {{ $selectedTransaction->transaction_date->format('M d, Y') }}
                            </p>
                        </div>
                        @if($selectedTransaction->category)
                            <div class="col-span-2">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Category</p>
                                <p class="text-sm font-medium text-card-foreground">{{ $selectedTransaction->category }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Description -->
                    @if($selectedTransaction->name !== $selectedTransaction->merchant_name)
                        <div>
                            <p class="text-xs font-medium text-muted-foreground mb-1">Full Description</p>
                            <p class="text-sm text-card-foreground">{{ $selectedTransaction->name }}</p>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="sticky bottom-0 bg-card border-t border-border p-6">
                    <button wire:click="closeDetails" 
                            class="w-full px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
