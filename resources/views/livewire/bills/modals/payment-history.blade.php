@if($showPaymentHistoryModal && $selectedBill)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
         x-data="{ show: @entangle('showPaymentHistoryModal') }"
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
                        <h3 class="text-xl font-semibold text-card-foreground">Payment History</h3>
                        <p class="text-sm text-muted-foreground mt-1">{{ $selectedBill->name }}</p>
                    </div>
                    <button wire:click="closeModal" 
                            class="p-2 text-muted-foreground hover:text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Payment Statistics -->
                @if($selectedBill->last_payment_date)
                    <div class="grid grid-cols-3 gap-4 mb-6 p-4 bg-muted rounded-[var(--radius-default)]">
                        <div>
                            <p class="text-xs text-muted-foreground mb-1">Last Payment</p>
                            <p class="text-lg font-semibold text-card-foreground">
                                {{ $selectedBill->currency }} {{ number_format((float) $selectedBill->last_payment_amount, 2) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-1">Payment Date</p>
                            <p class="text-lg font-semibold text-card-foreground">
                                {{ $selectedBill->last_payment_date->format('M d, Y') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-1">Days Since</p>
                            <p class="text-lg font-semibold text-card-foreground">
                                {{ $selectedBill->last_payment_date->diffInDays(now()) }}
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Payment History List -->
                <div class="space-y-3">
                    @if($selectedBill->last_payment_date)
                        <div class="flex items-center justify-between p-4 border border-border rounded-[var(--radius-default)] hover:bg-muted/50 transition-all duration-150">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-5 h-5 text-chart-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="font-semibold text-card-foreground">Payment Completed</p>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ $selectedBill->last_payment_date->format('F d, Y') }} at {{ $selectedBill->last_payment_date->format('g:i A') }}
                                </p>
                                @if($selectedBill->is_autopay)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-chart-3/20 text-chart-3 mt-2">
                                        Autopay
                                    </span>
                                @endif
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-xl font-semibold text-chart-2">
                                    {{ $selectedBill->currency }} {{ number_format((float) $selectedBill->last_payment_amount, 2) }}
                                </p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ $selectedBill->last_payment_date->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-4 text-sm font-medium text-card-foreground">No Payment History</h3>
                            <p class="mt-2 text-sm text-muted-foreground">This bill hasn't been paid yet.</p>
                        </div>
                    @endif
                </div>

                <!-- Payment Trends (if multiple payments existed) -->
                @if($selectedBill->last_payment_date && $selectedBill->last_payment_amount)
                    <div class="mt-6 p-4 bg-muted rounded-[var(--radius-default)]">
                        <h4 class="text-sm font-semibold text-card-foreground mb-3">Payment Insights</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Bill Amount</span>
                                <span class="font-medium text-card-foreground">
                                    {{ $selectedBill->currency }} {{ number_format(abs((float) $selectedBill->amount), 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Last Payment</span>
                                <span class="font-medium text-card-foreground">
                                    {{ $selectedBill->currency }} {{ number_format((float) $selectedBill->last_payment_amount, 2) }}
                                </span>
                            </div>
                            @php
                                $difference = abs((float) $selectedBill->amount) - (float) $selectedBill->last_payment_amount;
                            @endphp
                            @if(abs($difference) > 0.01)
                                <div class="flex justify-between pt-2 border-t border-border">
                                    <span class="text-muted-foreground">Difference</span>
                                    <span class="font-medium {{ $difference > 0 ? 'text-chart-2' : 'text-destructive' }}">
                                        {{ $difference > 0 ? '+' : '' }}{{ $selectedBill->currency }} {{ number_format($difference, 2) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-border">
                    <button wire:click="closeModal" 
                            class="px-4 py-2 text-sm font-medium border border-border text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

