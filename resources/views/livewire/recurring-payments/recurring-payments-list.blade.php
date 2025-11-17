<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-card-foreground">Recurring Payments</h2>
            <p class="text-sm text-muted-foreground mt-1">Automatically detected recurring charges from your transactions</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             x-init="setTimeout(() => show = false, 5000)"
             class="bg-chart-2/20 border border-chart-2 text-chart-2 px-4 py-3 rounded-[var(--radius-md)] relative" 
             role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
            <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-chart-2" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </button>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Recurring -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Recurring</p>
            <p class="text-3xl font-semibold text-card-foreground mt-1">{{ $summaryStats['total_recurring'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">Detected patterns</p>
        </div>

        <!-- Active Subscriptions -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Active</p>
            <p class="text-3xl font-semibold text-chart-2 mt-1">{{ $summaryStats['active_recurring'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">Currently subscribed</p>
        </div>

        <!-- Est. Monthly Cost -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Est. Monthly</p>
            <p class="text-3xl font-semibold text-chart-4 mt-1">${{ number_format($summaryStats['estimated_monthly'], 2) }}</p>
            <p class="text-xs text-muted-foreground mt-1">Estimated per month</p>
        </div>

        <!-- Total Paid (6mo) -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Paid (6mo)</p>
            <p class="text-3xl font-semibold text-card-foreground mt-1">${{ number_format($summaryStats['total_paid_6mo'], 2) }}</p>
            <p class="text-xs text-muted-foreground mt-1">Last 6 months</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Search -->
            <div class="flex-1">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search recurring payments..."
                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
            </div>

            <!-- Frequency Filter -->
            <select wire:model.live="frequencyFilter" 
                    class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                <option value="all">All Frequencies</option>
                <option value="weekly">Weekly</option>
                <option value="biweekly">Bi-Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
            </select>

            <!-- Status Filter -->
            <select wire:model.live="statusFilter" 
                    class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <!-- Reset Button -->
            <button wire:click="resetFilters" 
                    class="px-4 py-2 bg-muted text-muted-foreground rounded-[var(--radius-sm)] font-medium text-sm hover:bg-muted/80 transition-all duration-150">
                Reset
            </button>
        </div>
    </div>

    <!-- Recurring Payments List -->
    @if($recurringGroups->isEmpty())
        <!-- Empty State -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-card-foreground">No recurring payments detected</h3>
            <p class="mt-2 text-sm text-muted-foreground">
                @if($search || $frequencyFilter !== 'all' || $statusFilter !== 'all')
                    Try adjusting your filters to see more results.
                @else
                    We need more transaction data to detect recurring patterns. Connect your accounts and wait for transactions to sync.
                @endif
            </p>
        </div>
    @else
        <!-- Recurring Groups Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($recurringGroups as $group)
                <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm overflow-hidden hover:shadow-elevated transition-all duration-150"
                     wire:click="showDetails('{{ $group['id'] }}')"
                     role="button">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-lg font-semibold text-card-foreground truncate">
                                    {{ $group['display_name'] }}
                                </h4>
                                <p class="text-xs text-muted-foreground mt-1">{{ $group['category'] }}</p>
                            </div>
                            <!-- Status Badge -->
                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $group['is_active'] ? 'bg-chart-2/20 text-chart-2' : 'bg-muted text-muted-foreground' }}">
                                {{ $group['is_active'] ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <!-- Amount -->
                        <div class="mb-4">
                            <p class="text-sm text-muted-foreground">Average Amount</p>
                            <p class="text-3xl font-bold text-card-foreground">
                                ${{ number_format($group['average_amount'], 2) }}
                            </p>
                            @if($group['min_amount'] != $group['max_amount'])
                                <p class="text-xs text-muted-foreground mt-1">
                                    Range: ${{ number_format($group['min_amount'], 2) }} - ${{ number_format($group['max_amount'], 2) }}
                                </p>
                            @endif
                        </div>

                        <!-- Frequency & Last Paid -->
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="bg-muted/30 rounded-[var(--radius-md)] p-3">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Frequency</p>
                                <p class="text-sm font-semibold text-card-foreground">{{ $group['frequency'] }}</p>
                            </div>
                            <div class="bg-muted/30 rounded-[var(--radius-md)] p-3">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Occurrences</p>
                                <p class="text-sm font-semibold text-card-foreground">{{ $group['occurrence_count'] }}x</p>
                            </div>
                            <div class="bg-chart-2/20 rounded-[var(--radius-md)] p-3">
                                <p class="text-xs font-medium text-muted-foreground mb-1">Last Paid</p>
                                <p class="text-sm font-semibold text-card-foreground">{{ $group['last_payment_date']->format('M d') }}</p>
                            </div>
                        </div>

                        <!-- Next Payment -->
                        @if($group['next_expected_date'])
                            <div class="pt-3 border-t border-border/50">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-muted-foreground">Next Expected</p>
                                        <p class="text-sm font-semibold text-card-foreground">
                                            {{ $group['next_expected_date']->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-muted-foreground">
                                            {{ $group['next_expected_date']->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="pt-3 border-t border-border/50">
                                <p class="text-xs text-muted-foreground">
                                    Last payment: {{ $group['last_payment_date']->format('M d, Y') }} 
                                    <span class="text-muted-foreground/70">({{ $group['days_since_last'] }} days ago)</span>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Details Modal -->
    @if($showDetailsModal && $selectedGroup)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-data x-show="$wire.showDetailsModal" x-transition>
            <div class="bg-card border border-border rounded-[var(--radius-large)] shadow-elevated w-full max-w-3xl max-h-[90vh] overflow-y-auto" @click.away="$wire.closeDetails()">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-card border-b border-border p-6 z-10">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-semibold text-card-foreground">{{ $selectedGroup['display_name'] }}</h3>
                            <p class="text-sm text-muted-foreground mt-1">{{ $selectedGroup['category'] }}</p>
                        </div>
                        <button wire:click="closeDetails" class="text-muted-foreground hover:text-card-foreground">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6">
                    <!-- Summary Stats -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-muted/30 rounded-[var(--radius-md)] p-4 text-center">
                            <p class="text-xs font-medium text-muted-foreground mb-1">Average Amount</p>
                            <p class="text-2xl font-bold text-card-foreground">${{ number_format($selectedGroup['average_amount'], 2) }}</p>
                        </div>
                        <div class="bg-muted/30 rounded-[var(--radius-md)] p-4 text-center">
                            <p class="text-xs font-medium text-muted-foreground mb-1">Frequency</p>
                            <p class="text-2xl font-bold text-card-foreground">{{ $selectedGroup['frequency'] }}</p>
                        </div>
                        <div class="bg-muted/30 rounded-[var(--radius-md)] p-4 text-center">
                            <p class="text-xs font-medium text-muted-foreground mb-1">Total Paid</p>
                            <p class="text-2xl font-bold text-card-foreground">${{ number_format($selectedGroup['total_paid'], 2) }}</p>
                        </div>
                    </div>

                    <!-- Next Payment Info -->
                    @if($selectedGroup['next_expected_date'])
                        <div class="bg-chart-4/10 border border-chart-4/20 rounded-[var(--radius-md)] p-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-chart-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="font-semibold text-card-foreground">Next Expected Payment</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ $selectedGroup['next_expected_date']->format('l, F j, Y') }} 
                                        ({{ $selectedGroup['next_expected_date']->diffForHumans() }})
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Transaction History -->
                    <div>
                        <h4 class="text-lg font-semibold text-card-foreground mb-3">Transaction History ({{ count($selectedGroup['transactions']) }})</h4>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @foreach(array_reverse($selectedGroup['transactions']) as $transaction)
                                <div class="flex items-center justify-between p-3 bg-muted/30 rounded-[var(--radius-sm)] hover:bg-muted/50 transition-colors">
                                    <div>
                                        <p class="text-sm font-medium text-card-foreground">
                                            {{ $transaction->transaction_date->format('M d, Y') }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ $transaction->name }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-card-foreground">
                                            ${{ number_format($transaction->amount, 2) }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ $transaction->account->account_name ?? 'Unknown' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="sticky bottom-0 bg-card border-t border-border p-6">
                    <div class="flex items-center justify-end gap-3">
                        <button wire:click="closeDetails" 
                                class="px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
