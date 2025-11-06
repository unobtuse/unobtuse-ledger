@if($showDetailsModal && $selectedBill)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ show: @entangle('showDetailsModal') }"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            
            <!-- Modal -->
            <div class="relative bg-card border border-border rounded-[var(--radius-lg)] shadow-elevated max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-semibold text-card-foreground">{{ $selectedBill->name }}</h3>
                        @if($selectedBill->payee_name)
                            <p class="text-sm text-muted-foreground mt-1">{{ $selectedBill->payee_name }}</p>
                        @endif
                    </div>
                    <button wire:click="closeModal" 
                            class="p-2 text-muted-foreground hover:text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="space-y-6">
                    <!-- Status and Amount -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Status</p>
                            @php
                                $statusColors = [
                                    'paid' => 'bg-chart-2/20 text-chart-2',
                                    'upcoming' => 'bg-chart-4/20 text-chart-4',
                                    'due' => 'bg-chart-5/20 text-chart-5',
                                    'overdue' => 'bg-destructive/20 text-destructive',
                                    'scheduled' => 'bg-chart-3/20 text-chart-3',
                                ];
                                $statusColor = $statusColors[$selectedBill->payment_status] ?? 'bg-muted text-muted-foreground';
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColor }}">
                                {{ ucfirst($selectedBill->payment_status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Amount</p>
                            <p class="text-2xl font-semibold text-card-foreground">
                                {{ $selectedBill->currency }} {{ number_format(abs((float) $selectedBill->amount), 2) }}
                            </p>
                        </div>
                    </div>

                    <!-- Due Date Information -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Due Date</p>
                            <p class="text-card-foreground">{{ $selectedBill->next_due_date->format('M d, Y') }}</p>
                            @if($selectedBill->next_due_date->isPast() && $selectedBill->payment_status !== 'paid')
                                <p class="text-xs text-destructive mt-1">Overdue by {{ $selectedBill->next_due_date->diffForHumans() }}</p>
                            @elseif($selectedBill->next_due_date->isFuture())
                                <p class="text-xs text-muted-foreground mt-1">Due in {{ $selectedBill->next_due_date->diffForHumans() }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Frequency</p>
                            <p class="text-card-foreground">{{ ucfirst($selectedBill->frequency) }}</p>
                        </div>
                    </div>

                    <!-- Category and Priority -->
                    <div class="grid grid-cols-2 gap-4">
                        @if($selectedBill->category)
                            <div>
                                <p class="text-sm font-medium text-muted-foreground mb-1">Category</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-muted text-muted-foreground">
                                    {{ ucfirst($selectedBill->category) }}
                                </span>
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Priority</p>
                            @php
                                $priorityColors = [
                                    'low' => 'bg-chart-2/20 text-chart-2',
                                    'medium' => 'bg-chart-4/20 text-chart-4',
                                    'high' => 'bg-chart-5/20 text-chart-5',
                                    'critical' => 'bg-destructive/20 text-destructive',
                                ];
                                $priorityColor = $priorityColors[$selectedBill->priority] ?? 'bg-muted text-muted-foreground';
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $priorityColor }}">
                                {{ ucfirst($selectedBill->priority) }}
                            </span>
                        </div>
                    </div>

                    <!-- Autopay Information -->
                    @if($selectedBill->is_autopay)
                        <div class="p-4 bg-chart-3/10 border border-chart-3/30 rounded-[var(--radius-default)]">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-chart-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <p class="font-semibold text-card-foreground">Autopay Enabled</p>
                            </div>
                            @if($selectedBill->autopay_account)
                                <p class="text-sm text-muted-foreground">Account: {{ $selectedBill->autopay_account }}</p>
                            @endif
                        </div>
                    @endif

                    <!-- Payment Link -->
                    @if($selectedBill->payment_link)
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-2">Payment Link</p>
                            <a href="{{ $selectedBill->payment_link }}" 
                               target="_blank"
                               class="inline-flex items-center gap-2 text-primary hover:underline">
                                <span>{{ $selectedBill->payment_link }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    @endif

                    <!-- Description -->
                    @if($selectedBill->description)
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-2">Description</p>
                            <p class="text-card-foreground">{{ $selectedBill->description }}</p>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($selectedBill->notes)
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-2">Notes</p>
                            <p class="text-card-foreground whitespace-pre-wrap">{{ $selectedBill->notes }}</p>
                        </div>
                    @endif

                    <!-- Last Payment -->
                    @if($selectedBill->last_payment_date)
                        <div class="p-4 bg-muted rounded-[var(--radius-default)]">
                            <p class="text-sm font-medium text-muted-foreground mb-1">Last Payment</p>
                            <p class="text-card-foreground">
                                {{ $selectedBill->currency }} {{ number_format((float) $selectedBill->last_payment_amount, 2) }}
                                on {{ $selectedBill->last_payment_date->format('M d, Y') }}
                            </p>
                        </div>
                    @endif

                    <!-- Reminder Settings -->
                    @if($selectedBill->reminder_enabled)
                        <div>
                            <p class="text-sm font-medium text-muted-foreground mb-1">Reminder</p>
                            <p class="text-card-foreground">
                                {{ $selectedBill->reminder_days_before }} day(s) before due date
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-border">
                    <button wire:click="showPaymentHistory('{{ $selectedBill->id }}')" 
                            class="px-4 py-2 text-sm font-medium text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                        Payment History
                    </button>
                    @if($selectedBill->payment_status !== 'paid')
                        <button wire:click="markAsPaid('{{ $selectedBill->id }}')" 
                                class="px-4 py-2 text-sm font-medium bg-chart-2/20 text-chart-2 rounded-[var(--radius-sm)] hover:bg-chart-2/30 transition-all duration-150">
                            Mark as Paid
                        </button>
                    @endif
                    <button wire:click="edit('{{ $selectedBill->id }}')" 
                            class="px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-[var(--radius-sm)] hover:opacity-90 transition-all duration-150">
                        Edit
                    </button>
                    <button wire:click="closeModal" 
                            class="px-4 py-2 text-sm font-medium border border-border text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

