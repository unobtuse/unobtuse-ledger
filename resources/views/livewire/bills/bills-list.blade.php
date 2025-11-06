<div class="space-y-6" x-data @show-bill-details.window="$wire.showDetails($event.detail.billId)">
    @if($viewMode === 'calendar')
        <!-- Summary Cards (shown in calendar view too) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
                <p class="text-sm font-medium text-muted-foreground">Total Due</p>
                <p class="text-3xl font-semibold text-card-foreground mt-1">
                    {{ $summaryStats['total_due'] > 0 ? '$' . number_format($summaryStats['total_due'], 2) : '$0.00' }}
                </p>
            </div>
            <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
                <p class="text-sm font-medium text-muted-foreground">Paid This Month</p>
                <p class="text-3xl font-semibold text-chart-2 mt-1">{{ $summaryStats['paid_this_month'] }}</p>
            </div>
            <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
                <p class="text-sm font-medium text-muted-foreground">Upcoming</p>
                <p class="text-3xl font-semibold text-chart-4 mt-1">{{ $summaryStats['upcoming_count'] }}</p>
            </div>
            <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
                <p class="text-sm font-medium text-muted-foreground">Overdue</p>
                <p class="text-3xl font-semibold text-destructive mt-1">{{ $summaryStats['overdue_count'] }}</p>
            </div>
        </div>
        @livewire('bills.bills-calendar')
    @else
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Due Card -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Due</p>
            <p class="text-3xl font-semibold text-card-foreground mt-1">
                {{ $summaryStats['total_due'] > 0 ? '$' . number_format($summaryStats['total_due'], 2) : '$0.00' }}
            </p>
            <p class="text-xs text-muted-foreground mt-1">All unpaid bills</p>
        </div>

        <!-- Paid This Month Card -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Paid This Month</p>
            <p class="text-3xl font-semibold text-chart-2 mt-1">{{ $summaryStats['paid_this_month'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">Bills completed</p>
        </div>

        <!-- Upcoming Card -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Upcoming</p>
            <p class="text-3xl font-semibold text-chart-4 mt-1">{{ $summaryStats['upcoming_count'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">Due this month</p>
        </div>

        <!-- Overdue Card -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Overdue</p>
            <p class="text-3xl font-semibold text-destructive mt-1">{{ $summaryStats['overdue_count'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">Requires attention</p>
        </div>
    </div>

    <!-- Bills Due Before Next Payday -->
    @if($payScheduleInfo['exists'] && $billsDueBeforePayday->count() > 0)
        <div class="bg-card border border-destructive/30 rounded-[var(--radius-default)] shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-card-foreground">Due Before Next Payday</h3>
                    <p class="text-sm text-muted-foreground mt-1">
                        Next payday: {{ \Carbon\Carbon::parse($payScheduleInfo['next_pay_date'])->format('M d, Y') }}
                        ({{ $payScheduleInfo['days_until'] }} days)
                    </p>
                </div>
                @if($payScheduleInfo['net_pay'])
                    <div class="text-right">
                        <p class="text-sm text-muted-foreground">Available after bills</p>
                        <p class="text-xl font-semibold text-card-foreground">
                            ${{ number_format($payScheduleInfo['net_pay'] - $payScheduleInfo['total_due_before_payday'], 2) }}
                        </p>
                    </div>
                @endif
            </div>

            <!-- Progress Bar -->
            @if($payScheduleInfo['net_pay'])
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-muted-foreground mb-1">
                        <span>Bills: ${{ number_format($payScheduleInfo['total_due_before_payday'], 2) }}</span>
                        <span>Net Pay: ${{ number_format($payScheduleInfo['net_pay'], 2) }}</span>
                    </div>
                    <div class="w-full bg-muted rounded-full h-2 overflow-hidden">
                        @php
                            $percentage = min(100, ($payScheduleInfo['total_due_before_payday'] / $payScheduleInfo['net_pay']) * 100);
                        @endphp
                        <div class="h-full bg-destructive transition-all duration-300" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endif

            <div class="space-y-2">
                @foreach($billsDueBeforePayday as $bill)
                    <div class="flex items-center justify-between p-3 bg-muted rounded-lg hover:bg-muted/80 transition-colors">
                        <div class="flex-1">
                            <p class="font-medium text-card-foreground">{{ $bill->name }}</p>
                            <p class="text-sm text-muted-foreground">
                                Due {{ $bill->next_due_date->format('M d, Y') }}
                                @if($bill->category)
                                    • {{ ucfirst($bill->category) }}
                                @endif
                            </p>
                        </div>
                        <p class="text-lg font-semibold text-card-foreground ml-4">
                            ${{ number_format(abs((float) $bill->amount), 2) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filters and Actions -->
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <h2 class="text-xl font-semibold text-card-foreground">All Bills</h2>
            <div class="flex items-center gap-3">
                <!-- View Toggle -->
                <div class="flex items-center bg-muted rounded-lg p-1">
                    <button wire:click="$set('viewMode', 'list')" 
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-150 {{ $viewMode === 'list' ? 'bg-card text-card-foreground shadow-sm' : 'text-muted-foreground hover:text-card-foreground' }}">
                        List
                    </button>
                    <button wire:click="$set('viewMode', 'calendar')" 
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-150 {{ $viewMode === 'calendar' ? 'bg-card text-card-foreground shadow-sm' : 'text-muted-foreground hover:text-card-foreground' }}">
                        Calendar
                    </button>
                </div>
                <button wire:click="create" 
                        class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Bill
                </button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="lg:col-span-2">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search bills..."
                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
            </div>
            <select wire:model.live="statusFilter" 
                    class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                <option value="all">All Status</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
                <option value="overdue">Overdue</option>
                <option value="upcoming">Upcoming</option>
                <option value="due">Due</option>
            </select>
            <select wire:model.live="categoryFilter" 
                    class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                <option value="all">All Categories</option>
                <option value="rent">Rent</option>
                <option value="mortgage">Mortgage</option>
                <option value="utilities">Utilities</option>
                <option value="internet">Internet</option>
                <option value="phone">Phone</option>
                <option value="insurance">Insurance</option>
                <option value="subscription">Subscription</option>
                <option value="loan">Loan</option>
                <option value="credit_card">Credit Card</option>
                <option value="other">Other</option>
            </select>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedBills) > 0)
            <div class="mb-4 p-3 bg-muted rounded-lg flex items-center justify-between">
                <span class="text-sm text-card-foreground">{{ count($selectedBills) }} bill(s) selected</span>
                <button wire:click="bulkMarkAsPaid" 
                                class="px-3 py-1.5 text-sm bg-chart-2/20 text-chart-2 rounded-[var(--radius-sm)] hover:bg-chart-2/30 transition-all duration-150">
                    Mark All as Paid
                </button>
            </div>
        @endif

        <!-- Bills List -->
        <div class="space-y-3">
            @forelse($bills as $bill)
                <div class="flex items-center justify-between p-4 border border-border rounded-[var(--radius-default)] hover:bg-muted/50 transition-all duration-150">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <!-- Checkbox for bulk selection -->
                        <input type="checkbox" 
                               wire:model="selectedBills" 
                               value="{{ $bill->id }}"
                               class="w-4 h-4 rounded border-input text-primary focus:ring-ring">
                        
                        <!-- Bill Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-semibold text-card-foreground truncate">{{ $bill->name }}</h4>
                                @if($bill->auto_detected)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-chart-1/20 text-chart-1">
                                        Auto
                                    </span>
                                @endif
                                @if($bill->is_autopay)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-chart-3/20 text-chart-3">
                                        Autopay
                                    </span>
                                @endif
                                @if($bill->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground">
                                        {{ ucfirst($bill->category) }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                <span>Due: {{ $bill->next_due_date->format('M d, Y') }}</span>
                                <span>{{ ucfirst($bill->frequency) }}</span>
                                @if($bill->payee_name)
                                    <span>{{ $bill->payee_name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4 ml-4">
                        <!-- Status Badge -->
                        @php
                            $statusColors = [
                                'paid' => 'bg-chart-2/20 text-chart-2',
                                'upcoming' => 'bg-chart-4/20 text-chart-4',
                                'due' => 'bg-chart-5/20 text-chart-5',
                                'overdue' => 'bg-destructive/20 text-destructive',
                                'scheduled' => 'bg-chart-3/20 text-chart-3',
                            ];
                            $statusColor = $statusColors[$bill->payment_status] ?? 'bg-muted text-muted-foreground';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                            {{ ucfirst($bill->payment_status) }}
                        </span>
                        
                        <!-- Amount -->
                        <p class="text-lg font-semibold text-card-foreground min-w-[100px] text-right">
                            ${{ number_format(abs((float) $bill->amount), 2) }}
                        </p>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-2" x-data="{ open: false }">
                            @if($bill->payment_status !== 'paid')
                                <button wire:click="markAsPaid('{{ $bill->id }}')" 
                                        class="px-3 py-1.5 text-sm bg-chart-2/20 text-chart-2 rounded-[var(--radius-sm)] hover:bg-chart-2/30 transition-colors duration-150">
                                    Mark Paid
                                </button>
                            @endif
                            <button wire:click="showDetails('{{ $bill->id }}')" 
                                    class="p-2 text-muted-foreground hover:text-card-foreground transition-colors duration-150">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                            <button wire:click="edit('{{ $bill->id }}')" 
                                    class="p-2 text-muted-foreground hover:text-card-foreground transition-colors duration-150">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-medium text-card-foreground">No bills found</h3>
                    <p class="mt-2 text-sm text-muted-foreground">Add your first bill to start tracking payments.</p>
                    <div class="mt-6">
                        <button wire:click="create" 
                                class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                            Add Your First Bill
                        </button>
                    </div>
                </div>
            @endforelse
        </div>

        @if($bills->hasPages())
            <div class="mt-6">
                {{ $bills->links() }}
            </div>
        @endif
    </div>

    <!-- Include Modals -->
    @include('livewire.bills.modals.bill-details')
    @include('livewire.bills.modals.bill-form')
    @include('livewire.bills.modals.payment-history')
    @endif
</div>
