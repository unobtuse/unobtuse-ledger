<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-card-foreground">Late & Annual Fees</h2>
            <p class="text-sm text-muted-foreground mt-1">Track late payment fees, past due charges, and annual membership fees</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Fees -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Fees</p>
            <p class="text-3xl font-semibold text-destructive mt-1">${{ number_format($summaryStats['total_fees'], 2) }}</p>
            <p class="text-xs text-muted-foreground mt-1">All time</p>
        </div>

        <!-- Fee Count -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Transactions</p>
            <p class="text-3xl font-semibold text-card-foreground mt-1">{{ $summaryStats['fee_count'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">Fee charges</p>
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

        <!-- Average Fee -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Average Fee</p>
            <p class="text-3xl font-semibold text-card-foreground mt-1">${{ number_format($summaryStats['average_fee'], 2) }}</p>
            <p class="text-xs text-muted-foreground mt-1">Per transaction</p>
        </div>
    </div>

    <!-- Fees by Type -->
    @if($feesByType->isNotEmpty())
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Fees by Category</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($feesByType as $feeGroup)
                    <div class="bg-muted/30 rounded-[var(--radius-md)] p-4 cursor-pointer hover:bg-muted/50 transition-colors"
                         wire:click="$set('feeTypeFilter', '{{ $feeGroup['type'] }}')">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">{{ $feeGroup['icon'] }}</span>
                                <span class="text-sm font-medium text-card-foreground">{{ $feeGroup['label'] }}</span>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ $feeGroup['count'] }}x</span>
                        </div>
                        <p class="text-2xl font-bold text-destructive">${{ number_format($feeGroup['total'], 2) }}</p>
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
                           placeholder="Search fees..."
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

            <!-- Row 2: Fee Type, Account, and Reset -->
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Fee Type Filter -->
                <select wire:model.live="feeTypeFilter" 
                        class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                    <option value="all">All Fee Types</option>
                    @foreach($feePatterns as $type => $pattern)
                        <option value="{{ $type }}">{{ $pattern['icon'] }} {{ $pattern['label'] }}</option>
                    @endforeach
                </select>

                <!-- Account Filter -->
                <select wire:model.live="accountFilter" 
                        class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
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

    <!-- Fees List -->
    @if($allFees->isEmpty())
        <!-- Empty State -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-card-foreground">No late or annual fees found</h3>
            <p class="mt-2 text-sm text-muted-foreground">
                @if($search || $feeTypeFilter !== 'all' || $accountFilter || $dateRange !== 'all')
                    Try adjusting your filters to see more results.
                @else
                    Great news! No late or annual fees detected in your transactions.
                @endif
            </p>
        </div>
    @else
        <!-- Fees Table -->
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Fee Type
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
                        @foreach($allFees as $fee)
                            @php
                                $feeType = null;
                                foreach($feePatterns as $type => $pattern) {
                                    $searchText = strtolower($fee->name . ' ' . $fee->merchant_name . ' ' . $fee->category);
                                    foreach($pattern['keywords'] as $keyword) {
                                        if(stripos($searchText, $keyword) !== false) {
                                            $feeType = $pattern;
                                            break 2;
                                        }
                                    }
                                }
                                if(!$feeType) $feeType = $feePatterns['other_fee'];
                            @endphp
                            <tr class="hover:bg-muted/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-card-foreground">
                                    {{ $fee->transaction_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-sm">
                                        <span>{{ $feeType['icon'] }}</span>
                                        <span class="text-muted-foreground">{{ $feeType['label'] }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-card-foreground">
                                    <div class="max-w-md">
                                        <p class="font-medium truncate">{{ $fee->merchant_name ?? $fee->name }}</p>
                                        @if($fee->merchant_name && $fee->merchant_name !== $fee->name)
                                            <p class="text-xs text-muted-foreground truncate">{{ $fee->name }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ $fee->account->display_name_without_mask ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-destructive">
                                    ${{ number_format($fee->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <button wire:click="showDetails('{{ $fee->id }}')"
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
                {{ $allFees->links() }}
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
                        <p class="text-sm text-muted-foreground mb-1">Fee Amount</p>
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
                        @if($selectedTransaction->payment_channel)
                            <div>
                                <p class="text-xs font-medium text-muted-foreground mb-1">Payment Channel</p>
                                <p class="text-sm font-medium text-card-foreground">{{ ucfirst($selectedTransaction->payment_channel) }}</p>
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
