<div class="space-y-6">
    @if($activeSchedule)
        <!-- Hero Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Next Payday Card -->
            <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-6 transition-all duration-150 ease-in-out hover:shadow-md">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-muted-foreground">Next Payday</p>
                    <svg class="w-5 h-5 text-chart-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                @if(count($upcomingPayDates) > 0)
                    @php
                        $nextPayDate = \Carbon\Carbon::parse($upcomingPayDates[0]);
                        $daysUntil = $activeSchedule->days_until_payday;
                    @endphp
                    <p class="text-2xl font-semibold text-card-foreground mb-1">
                        {{ $daysUntil > 0 ? $daysUntil : 0 }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ $daysUntil === 0 ? 'Today!' : ($daysUntil === 1 ? 'day' : 'days') }} until {{ $nextPayDate->format('M d') }}
                    </p>
                @else
                    <p class="text-2xl font-semibold text-card-foreground">N/A</p>
                @endif
            </div>

            <!-- Net Pay Card -->
            <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-6 transition-all duration-150 ease-in-out hover:shadow-md">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-muted-foreground">Net Pay</p>
                    <svg class="w-5 h-5 text-chart-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-2xl font-semibold text-card-foreground mb-1">
                    {{ $activeSchedule->formatted_net_pay }}
                </p>
                <p class="text-xs text-muted-foreground">Per pay period</p>
            </div>

            <!-- Bills Due Card -->
            <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-6 transition-all duration-150 ease-in-out hover:shadow-md">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-muted-foreground">Bills Due</p>
                    <svg class="w-5 h-5 text-chart-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="text-2xl font-semibold text-card-foreground mb-1">
                    {{ $activeSchedule->currency }} {{ number_format($totalBillsDue, 2) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ $billsDue->count() }} {{ $billsDue->count() === 1 ? 'bill' : 'bills' }} before payday
                </p>
            </div>

            <!-- Available After Bills Card -->
            <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-6 transition-all duration-150 ease-in-out hover:shadow-md">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-muted-foreground">Available</p>
                    <svg class="w-5 h-5 text-chart-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-2xl font-semibold text-card-foreground mb-1">
                    {{ $activeSchedule->formatted_available_after_bills }}
                </p>
                <p class="text-xs text-muted-foreground">After bills paid</p>
            </div>
        </div>

        <!-- Pay Schedule Overview & Calendar Timeline -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Pay Schedule Details -->
            <div class="lg:col-span-1 bg-card border border-border rounded-[0.625rem] shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-card-foreground">Pay Schedule</h2>
                    <button wire:click="edit('{{ $activeSchedule->id }}')" 
                            class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-150">
                        Edit
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Frequency</p>
                        <p class="text-base font-medium text-card-foreground">{{ ucfirst($activeSchedule->frequency) }}</p>
                    </div>
                    @if($activeSchedule->employer_name)
                        <div>
                            <p class="text-xs text-muted-foreground mb-1">Employer</p>
                            <p class="text-base font-medium text-card-foreground">{{ $activeSchedule->employer_name }}</p>
                        </div>
                    @endif
                    @if($activeSchedule->gross_pay)
                        <div>
                            <p class="text-xs text-muted-foreground mb-1">Gross Pay</p>
                            <p class="text-base font-medium text-card-foreground">{{ $activeSchedule->formatted_gross_pay }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Net Pay</p>
                        <p class="text-base font-medium text-card-foreground">{{ $activeSchedule->formatted_net_pay }}</p>
                    </div>
                    @if($activeSchedule->next_pay_date)
                        <div>
                            <p class="text-xs text-muted-foreground mb-1">Next Pay Date</p>
                            <p class="text-base font-medium text-chart-2">{{ $activeSchedule->next_pay_date->format('M d, Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Calendar Timeline -->
            <div class="lg:col-span-2 bg-card border border-border rounded-[0.625rem] shadow-sm p-6">
                <h3 class="text-lg font-semibold text-card-foreground mb-4">Upcoming Pay Dates</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($upcomingPayDates as $index => $date)
                        @php
                            $payDate = \Carbon\Carbon::parse($date);
                            $isNext = $index === 0;
                        @endphp
                        <div class="p-4 bg-muted rounded-lg border {{ $isNext ? 'border-chart-2 border-2' : 'border-border' }} transition-all duration-150 ease-in-out hover:shadow-sm">
                            <p class="text-xs text-muted-foreground mb-1">
                                {{ $isNext ? 'Next' : 'Pay #' . ($index + 1) }}
                            </p>
                            <p class="text-base font-semibold text-card-foreground">
                                {{ $payDate->format('M d') }}
                            </p>
                            <p class="text-xs text-muted-foreground mt-1">
                                {{ $payDate->format('Y') }} • {{ $payDate->diffForHumans() }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Bills Due Before Payday -->
        @if($billsDue->count() > 0)
            <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-card-foreground">Bills Due Before Next Payday</h3>
                    <a href="{{ route('bills.index') }}" class="text-sm text-primary hover:underline transition-colors duration-150">
                        View All Bills →
                    </a>
                </div>
                <div class="space-y-3">
                    @foreach($billsDue as $bill)
                        <div class="flex items-center justify-between p-4 bg-muted rounded-lg border border-border hover:bg-muted/80 transition-colors duration-150">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="font-medium text-card-foreground">{{ $bill->name }}</h4>
                                    @if($bill->category)
                                        <span class="text-xs px-2 py-0.5 bg-chart-1/20 text-chart-1 rounded-full">
                                            {{ ucfirst($bill->category) }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Due {{ $bill->next_due_date->format('M d, Y') }} • {{ $bill->days_until_due }} {{ $bill->days_until_due === 1 ? 'day' : 'days' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-base font-semibold text-card-foreground">{{ $bill->formatted_amount }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-border">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-card-foreground">Total Due</p>
                        <p class="text-lg font-semibold text-card-foreground">
                            {{ $activeSchedule->currency }} {{ number_format($totalBillsDue, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Budget Allocation Breakdown -->
        @if($activeSchedule->net_pay)
            <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-6">
                <h3 class="text-lg font-semibold text-card-foreground mb-4">Budget Allocation</h3>
                <div class="space-y-4">
                    <!-- Rent Allocation (25%) -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-chart-1"></div>
                                <p class="text-sm font-medium text-card-foreground">Rent Allocation</p>
                            </div>
                            <p class="text-sm font-semibold text-card-foreground">{{ $activeSchedule->formatted_rent_allocation }}</p>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            <div class="bg-chart-1 h-2 rounded-full" style="width: 25%"></div>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">25% of net pay</p>
                    </div>

                    <!-- Bills Due -->
                    @if($totalBillsDue > 0)
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-chart-3"></div>
                                    <p class="text-sm font-medium text-card-foreground">Bills Due</p>
                                </div>
                                <p class="text-sm font-semibold text-card-foreground">
                                    {{ $activeSchedule->currency }} {{ number_format($totalBillsDue, 2) }}
                                </p>
                            </div>
                            <div class="w-full bg-muted rounded-full h-2">
                                @php
                                    $billsPercentage = min(100, ($totalBillsDue / $activeSchedule->net_pay) * 100);
                                @endphp
                                <div class="bg-chart-3 h-2 rounded-full" style="width: {{ $billsPercentage }}%"></div>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">
                                {{ number_format($billsPercentage, 1) }}% of net pay
                            </p>
                        </div>
                    @endif

                    <!-- Available After Bills -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-chart-2"></div>
                                <p class="text-sm font-medium text-card-foreground">Available After Bills</p>
                            </div>
                            <p class="text-sm font-semibold text-card-foreground">{{ $activeSchedule->formatted_available_after_bills }}</p>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            @php
                                $availablePercentage = min(100, ($activeSchedule->available_after_bills / $activeSchedule->net_pay) * 100);
                            @endphp
                            <div class="bg-chart-2 h-2 rounded-full" style="width: {{ $availablePercentage }}%"></div>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ number_format($availablePercentage, 1) }}% of net pay remaining
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Income Projection -->
        @if(count($incomeProjection) > 0)
            <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-6">
                <h3 class="text-lg font-semibold text-card-foreground mb-4">Income Projection (Next 6 Months)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-4 text-sm font-medium text-muted-foreground">Month</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Pay Periods</th>
                                <th class="text-right py-3 px-4 text-sm font-medium text-muted-foreground">Total Income</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incomeProjection as $projection)
                                <tr class="border-b border-border hover:bg-muted/50 transition-colors duration-150">
                                    <td class="py-3 px-4 text-sm text-card-foreground">{{ $projection['month'] }}</td>
                                    <td class="py-3 px-4 text-sm text-card-foreground text-right">{{ $projection['pay_count'] }}</td>
                                    <td class="py-3 px-4 text-sm font-semibold text-card-foreground text-right">
                                        {{ $activeSchedule->currency }} {{ number_format($projection['total'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-card-foreground">No Pay Schedule Set</h3>
            <p class="mt-2 text-sm text-muted-foreground">Set up your pay schedule to track bills due before payday and manage your budget.</p>
            <button wire:click="create" 
                    class="mt-6 inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:opacity-90 transition-opacity duration-150">
                Create Pay Schedule
            </button>
        </div>
    @endif

    <!-- All Pay Schedules -->
    @if($paySchedules->count() > 0)
        <div class="bg-card border border-border rounded-[0.625rem] shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-card-foreground">All Pay Schedules</h3>
                <button wire:click="create" 
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:opacity-90 transition-opacity duration-150">
                    Add New
                </button>
            </div>
            <div class="space-y-3">
                @foreach($paySchedules as $schedule)
                    <div class="flex items-center justify-between p-4 border border-border rounded-lg hover:bg-muted/50 transition-colors duration-150">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <h4 class="font-semibold text-card-foreground">{{ ucfirst($schedule->frequency) }}</h4>
                                @if($schedule->is_active)
                                    <span class="text-xs px-2 py-0.5 bg-chart-2/20 text-chart-2 rounded-full font-medium">Active</span>
                                @endif
                                @if($schedule->employer_name)
                                    <span class="text-xs text-muted-foreground">• {{ $schedule->employer_name }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ $schedule->formatted_net_pay }} per pay period
                                @if($schedule->next_pay_date)
                                    • Next: {{ $schedule->next_pay_date->format('M d, Y') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(!$schedule->is_active)
                                <button wire:click="activate('{{ $schedule->id }}')" 
                                        class="px-3 py-1.5 text-sm border border-border rounded-lg text-muted-foreground hover:bg-muted transition-colors duration-150">
                                    Activate
                                </button>
                            @endif
                            <button wire:click="edit('{{ $schedule->id }}')" 
                                    class="px-3 py-1.5 text-sm border border-border rounded-lg text-muted-foreground hover:bg-muted transition-colors duration-150">
                                Edit
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Create/Edit Modal -->
    @if($showCreateModal || $showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
                <div class="relative bg-card border border-border rounded-[0.75rem] shadow-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-card-foreground">
                            {{ $showEditModal ? 'Edit Pay Schedule' : 'Create Pay Schedule' }}
                        </h3>
                        <button wire:click="closeModal" class="text-muted-foreground hover:text-foreground transition-colors duration-150">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form wire:submit.prevent="save" class="space-y-4">
                        <!-- Frequency -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Frequency *</label>
                            <select wire:model.live="frequency" required
                                    class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Bi-weekly</option>
                                <option value="semimonthly">Semi-monthly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                            @error('frequency') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <!-- Employer Name -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Employer Name</label>
                            <input type="text" wire:model="employer_name"
                                   placeholder="e.g., Acme Corp"
                                   class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                            @error('employer_name') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <!-- Net Pay -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Net Pay (Take Home) *</label>
                            <div class="flex gap-2">
                                <select wire:model="currency" class="px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="CAD">CAD</option>
                                </select>
                                <input type="number" step="0.01" wire:model="net_pay" required
                                       placeholder="0.00"
                                       class="flex-1 px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                            </div>
                            @error('net_pay') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                            @error('currency') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <!-- Gross Pay (Optional) -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Gross Pay (Before Taxes) <span class="text-muted-foreground">(Optional)</span></label>
                            <input type="number" step="0.01" wire:model="gross_pay"
                                   placeholder="0.00"
                                   class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                            @error('gross_pay') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <!-- Next Pay Date -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Next Pay Date *</label>
                            <input type="date" wire:model="next_pay_date" required
                                   class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                            @error('next_pay_date') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <!-- Frequency-specific fields -->
                        @if($frequency === 'weekly' || $frequency === 'biweekly')
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Pay Day of Week *</label>
                                <select wire:model="pay_day_of_week" required
                                        class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                                    <option value="">Select day</option>
                                    <option value="monday">Monday</option>
                                    <option value="tuesday">Tuesday</option>
                                    <option value="wednesday">Wednesday</option>
                                    <option value="thursday">Thursday</option>
                                    <option value="friday">Friday</option>
                                    <option value="saturday">Saturday</option>
                                    <option value="sunday">Sunday</option>
                                </select>
                                @error('pay_day_of_week') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                            </div>
                        @elseif($frequency === 'semimonthly')
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-card-foreground mb-2">First Pay Day (1-31) *</label>
                                    <input type="number" min="1" max="31" wire:model="pay_day_of_month_1" required
                                           class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                                    @error('pay_day_of_month_1') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-card-foreground mb-2">Second Pay Day (1-31) *</label>
                                    <input type="number" min="1" max="31" wire:model="pay_day_of_month_2" required
                                           class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                                    @error('pay_day_of_month_2') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @elseif($frequency === 'monthly')
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Pay Day of Month (1-31) *</label>
                                <input type="number" min="1" max="31" wire:model="pay_day_of_month_1" required
                                       class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150">
                                @error('pay_day_of_month_1') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Notes</label>
                            <textarea wire:model="notes" rows="3"
                                      placeholder="Any additional notes about this pay schedule..."
                                      class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-150"></textarea>
                            @error('notes') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex gap-3 pt-4 border-t border-border">
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:opacity-90 transition-opacity duration-150">
                                {{ $showEditModal ? 'Update' : 'Create' }}
                            </button>
                            <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 border border-border rounded-lg text-muted-foreground hover:bg-muted transition-colors duration-150">
                                Cancel
                            </button>
                            @if($showEditModal && $selectedScheduleId)
                                <button type="button" wire:click="delete('{{ $selectedScheduleId }}')" onclick="return confirm('Are you sure you want to delete this pay schedule?')"
                                        class="px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:opacity-90 transition-opacity duration-150">
                                    Delete
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
