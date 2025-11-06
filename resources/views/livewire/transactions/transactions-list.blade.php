<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Income</p>
                    <p class="text-2xl font-semibold text-card-foreground mt-1">
                        ${{ number_format($summaryStats['total_income'], 2) }}
                    </p>
                </div>
                <div class="p-3 bg-chart-2/10 rounded-lg">
                    <svg class="w-6 h-6 text-chart-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Expenses</p>
                    <p class="text-2xl font-semibold text-card-foreground mt-1">
                        ${{ number_format($summaryStats['total_expenses'], 2) }}
                    </p>
                </div>
                <div class="p-3 bg-destructive/10 rounded-lg">
                    <svg class="w-6 h-6 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Net Change</p>
                    <p class="text-2xl font-semibold {{ $summaryStats['net_change'] >= 0 ? 'text-chart-2' : 'text-destructive' }} mt-1">
                        {{ $summaryStats['net_change'] >= 0 ? '+' : '' }}${{ number_format($summaryStats['net_change'], 2) }}
                    </p>
                </div>
                <div class="p-3 {{ $summaryStats['net_change'] >= 0 ? 'bg-chart-2/10' : 'bg-destructive/10' }} rounded-lg">
                    <svg class="w-6 h-6 {{ $summaryStats['net_change'] >= 0 ? 'text-chart-2' : 'text-destructive' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Transactions</p>
                    <p class="text-2xl font-semibold text-card-foreground mt-1">
                        {{ number_format($summaryStats['transaction_count']) }}
                    </p>
                </div>
                <div class="p-3 bg-chart-1/10 rounded-lg">
                    <svg class="w-6 h-6 text-chart-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-chart-2/10 border border-chart-2/30 rounded-lg p-4 mb-4">
            <p class="text-sm text-chart-2">{{ session('message') }}</p>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-destructive/10 border border-destructive/30 rounded-lg p-4 mb-4">
            <p class="text-sm text-destructive">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Filters and Search -->
    <div class="bg-card border border-border rounded-lg shadow-sm p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <h2 class="text-xl font-semibold text-card-foreground">All Transactions</h2>
            <div class="flex items-center gap-2">
                <button wire:click="resetFilters" 
                        class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground border border-border rounded-lg hover:bg-muted transition-colors">
                    Reset Filters
                </button>
                <button wire:click="exportAll" 
                        class="px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:opacity-90 transition-all duration-150">
                    Export CSV
                </button>
                <button wire:click="$set('showCreateTransactionModal', true)"
                        class="px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:opacity-90 transition-all duration-150 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Transaction
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-4">
            <div class="relative">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search by merchant or description..."
                       class="w-full px-4 py-2 pl-10 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-muted-foreground mb-1">Account</label>
                <select wire:model.live="accountFilter" 
                        wire:loading.attr="disabled"
                        class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-muted-foreground mb-1">Category</label>
                <select wire:model.live="categoryFilter" 
                        class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-muted-foreground mb-1">Type</label>
                <select wire:model.live="typeFilter" 
                        class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="">All Types</option>
                    <option value="debit">Expenses (Debit)</option>
                    <option value="credit">Income (Credit)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-muted-foreground mb-1">Recurring</label>
                <select wire:model.live="recurringFilter" 
                        class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="">All</option>
                    <option value="recurring">Recurring Only</option>
                    <option value="one-time">One-Time Only</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-muted-foreground mb-1">From Date</label>
                <input type="date" 
                       wire:model.live="dateFrom" 
                       class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
            </div>

            <div>
                <label class="block text-sm font-medium text-muted-foreground mb-1">To Date</label>
                <input type="date" 
                       wire:model.live="dateTo" 
                       class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
            </div>
        </div>

        <!-- Per Page Selector -->
        <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <label class="text-sm text-muted-foreground">Show</label>
                <select wire:model.live="perPage" 
                        class="px-3 py-1 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <label class="text-sm text-muted-foreground">entries</label>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    @if(count($selectedTransactions) > 0)
        <div class="bg-primary/10 border border-primary/30 rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-card-foreground">
                    {{ count($selectedTransactions) }} transaction(s) selected
                </span>
                <div class="flex items-center gap-2">
                    <button wire:click="openBulkCategoryModal"
                            class="px-3 py-1.5 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:opacity-90 transition-all duration-150">
                        Categorize
                    </button>
                    <button wire:click="bulkDelete" 
                            wire:confirm="Are you sure you want to delete {{ count($selectedTransactions) }} transaction(s)?"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 text-sm font-medium bg-destructive text-destructive-foreground rounded-lg hover:opacity-90 transition-all duration-150 disabled:opacity-50">
                        <span wire:loading.remove>Delete</span>
                        <span wire:loading>Deleting...</span>
                    </button>
                    <button wire:click="exportSelected"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 text-sm font-medium border border-border rounded-lg hover:bg-muted transition-colors disabled:opacity-50">
                        <span wire:loading.remove>Export Selected</span>
                        <span wire:loading>Exporting...</span>
                    </button>
                </div>
            </div>
            <button wire:click="clearSelection" 
                    class="text-sm text-muted-foreground hover:text-card-foreground transition-colors">
                Clear Selection
            </button>
        </div>
    @endif

    <!-- Transactions Table -->
    <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                            <input type="checkbox" 
                                   wire:model="selectAll"
                                   wire:change="toggleSelectAll"
                                   class="w-4 h-4 text-primary bg-background border-input rounded focus:ring-ring">
                        </th>
                        <th wire:click="sortBy('transaction_date')" 
                            class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-card-foreground transition-colors">
                            <div class="flex items-center gap-2">
                                Date
                                @if($sortField === 'transaction_date')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('merchant_name')" 
                            class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-card-foreground transition-colors">
                            <div class="flex items-center gap-2">
                                Merchant
                                @if($sortField === 'merchant_name')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                            Category
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                            Account
                        </th>
                        <th wire:click="sortBy('amount')" 
                            class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider cursor-pointer hover:text-card-foreground transition-colors">
                            <div class="flex items-center justify-end gap-2">
                                Amount
                                @if($sortField === 'amount')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-muted transition-colors {{ in_array($transaction->id, $selectedTransactions) ? 'bg-primary/5' : '' }}" 
                            wire:click="showDetails('{{ $transaction->id }}')">
                            <td class="px-6 py-4 whitespace-nowrap" wire:click.stop="toggleSelection('{{ $transaction->id }}')">
                                <input type="checkbox" 
                                       {{ in_array($transaction->id, $selectedTransactions) ? 'checked' : '' }}
                                       wire:click.stop="toggleSelection('{{ $transaction->id }}')"
                                       class="w-4 h-4 text-primary bg-background border-input rounded focus:ring-ring cursor-pointer">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-card-foreground">
                                {{ $transaction->transaction_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div>
                                        <div class="font-medium text-card-foreground">{{ $transaction->merchant_name ?? $transaction->name }}</div>
                                        @if($transaction->name && $transaction->merchant_name)
                                            <div class="text-xs text-muted-foreground">{{ $transaction->name }}</div>
                                        @endif
                                    </div>
                                    @if($transaction->is_recurring)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-chart-4/20 text-chart-4" title="Recurring: {{ $transaction->recurring_frequency ?? 'Yes' }}">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            {{ ucfirst($transaction->recurring_frequency ?? 'Recurring') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $displayCategories = $transaction->categories;
                                @endphp
                                @if(count($displayCategories) > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-chart-1/20 text-chart-1">
                                        {{ $displayCategories[0] }}
                                    </span>
                                    @if(count($displayCategories) > 1)
                                        <span class="text-xs text-muted-foreground ml-1">+{{ count($displayCategories) - 1 }}</span>
                                    @endif
                                @else
                                    <span class="text-muted-foreground text-xs">Uncategorized</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                {{ $transaction->account->account_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium {{ $transaction->amount > 0 ? 'text-chart-2' : 'text-card-foreground' }}">
                                {{ $transaction->amount > 0 ? '+' : '' }}${{ number_format(abs($transaction->amount), 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click.stop="openCategoryModal('{{ $transaction->id }}')" 
                                            class="text-primary hover:text-primary/80 transition-colors" title="Edit Category">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="showDetails('{{ $transaction->id }}')" 
                                            class="text-primary hover:text-primary/80 transition-colors" title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="mt-4 text-sm font-medium text-card-foreground">No transactions found</h3>
                                <p class="mt-2 text-sm text-muted-foreground">Try adjusting your filters or link a bank account to start tracking transactions.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-border">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Transaction Details Modal -->
    @if($showDetailsModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showDetailsModal') }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="show" 
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 transition-opacity bg-black bg-opacity-50" 
                     @click="$wire.closeDetails()"></div>

                <!-- Modal panel -->
                <div x-show="show"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-card border border-border rounded-lg shadow-elevated">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-border">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-card-foreground">Transaction Details</h3>
                            <button wire:click="closeDetails" 
                                    class="text-muted-foreground hover:text-card-foreground transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center justify-between py-4 border-b border-border">
                            <div>
                                <p class="text-sm text-muted-foreground">Amount</p>
                                <p class="text-3xl font-semibold {{ $selectedTransaction->amount > 0 ? 'text-chart-2' : 'text-card-foreground' }}">
                                    {{ $selectedTransaction->amount > 0 ? '+' : '' }}${{ number_format(abs($selectedTransaction->amount), 2) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-muted-foreground">Date</p>
                                <p class="text-lg font-medium text-card-foreground">
                                    {{ $selectedTransaction->transaction_date->format('M d, Y') }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Merchant</p>
                            <p class="text-base text-card-foreground">{{ $selectedTransaction->merchant_name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Description</p>
                            <p class="text-base text-card-foreground">{{ $selectedTransaction->name }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Account</p>
                            <p class="text-base text-card-foreground">{{ $selectedTransaction->account->account_name ?? 'N/A' }}</p>
                        </div>

                        @php
                            $displayCategories = $selectedTransaction->categories;
                        @endphp
                        @if(count($displayCategories) > 0)
                            <div>
                                <p class="text-sm font-medium text-muted-foreground mb-2">Categories</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($displayCategories as $category)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-chart-1/20 text-chart-1">
                                            {{ $category }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($selectedTransaction->is_recurring)
                            <div>
                                <p class="text-sm font-medium text-muted-foreground mb-1">Recurring</p>
                                <p class="text-sm text-card-foreground">
                                    Yes - {{ ucfirst($selectedTransaction->recurring_frequency ?? 'Recurring') }}
                                </p>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground mb-1">Transaction ID</p>
                                <p class="text-sm text-card-foreground font-mono">{{ $selectedTransaction->id }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-muted-foreground mb-1">Pending</p>
                                <p class="text-sm text-card-foreground">{{ $selectedTransaction->pending ? 'Yes' : 'No' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-border bg-muted">
                        <button wire:click="closeDetails" 
                                class="w-full px-4 py-2 bg-primary text-primary-foreground rounded-lg font-medium hover:opacity-90 transition-all duration-150">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Category Modal -->
    @if($showEditCategoryModal)
        @livewire('transactions.edit-category', [
            'transactionId' => $selectedTransactionId,
            'transactionIds' => $selectedTransactions
        ], key('edit-category-' . now()->timestamp))
    @endif

    <!-- Create Transaction Modal -->
    @if($showCreateTransactionModal)
        @livewire('transactions.create-transaction', key('create-transaction-' . now()->timestamp))
    @endif
</div>


