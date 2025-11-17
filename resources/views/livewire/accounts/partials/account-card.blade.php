<div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm overflow-hidden hover:shadow-elevated transition-all duration-150" 
     x-data="{ showActions: false }">
    <!-- Card Header -->
    <div class="p-6 cursor-pointer" wire:click="toggleExpand('{{ $account->id }}')">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0">
                <!-- Institution Name -->
                <p class="text-sm font-medium text-muted-foreground mb-1">{{ $account->institution_name }}</p>
                <!-- Account Name -->
                <h4 class="text-lg font-semibold text-card-foreground truncate">
                    {{ $account->display_name_without_mask }}
                </h4>
                @if($account->mask)
                    <p class="text-xs text-muted-foreground mt-1">•••• {{ $account->mask }}</p>
                @endif
            </div>
            
            <!-- Action Menu -->
            <div class="relative ml-4" @click.stop>
                <button @click="showActions = !showActions" 
                        class="p-2 text-muted-foreground hover:text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div x-show="showActions" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     @click.away="showActions = false"
                     class="absolute right-0 mt-2 w-48 bg-popover border border-border rounded-[var(--radius-md)] shadow-elevated z-10"
                     style="display: none;">
                    <div class="py-1">
                        <button wire:click="refreshBalance('{{ $account->id }}')" 
                                class="w-full text-left px-4 py-2 text-sm text-popover-foreground hover:bg-muted transition-colors">
                            Refresh Balance
                        </button>
                        <a href="{{ route('transactions.index', ['account' => $account->id]) }}" 
                           class="block w-full text-left px-4 py-2 text-sm text-popover-foreground hover:bg-muted transition-colors">
                            View Transactions
                        </a>
                        <button wire:click="editNickname('{{ $account->id }}')" 
                                class="w-full text-left px-4 py-2 text-sm text-popover-foreground hover:bg-muted transition-colors">
                            Edit Nickname
                        </button>
                        @if(!$account->hasPaymentDue())
                            <button wire:click="openEditDueDate('{{ $account->id }}')" 
                                    class="w-full text-left px-4 py-2 text-sm text-popover-foreground hover:bg-muted transition-colors">
                                Add Due Date
                            </button>
                        @else
                            <button wire:click="openEditDueDate('{{ $account->id }}')" 
                                    class="w-full text-left px-4 py-2 text-sm text-popover-foreground hover:bg-muted transition-colors">
                                Edit Due Date
                            </button>
                        @endif
                        <div class="border-t border-border my-1"></div>
                        <button wire:click="confirmDisconnect('{{ $account->id }}')" 
                                class="w-full text-left px-4 py-2 text-sm text-destructive hover:bg-destructive/10 transition-colors">
                            Disconnect Account
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Type Badge -->
        <div class="flex items-center gap-2 mb-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground">
                {{ ucfirst(str_replace('_', ' ', $account->account_type)) }}
            </span>
            @if($account->account_subtype)
                <span class="text-xs text-muted-foreground">{{ ucfirst($account->account_subtype) }}</span>
            @endif
        </div>

        <!-- Balance -->
        <div class="mb-4">
            <p class="text-3xl font-semibold text-card-foreground">
                {{ $account->formatted_balance }}
            </p>
            @if($account->available_balance && $account->available_balance != $account->balance)
                <p class="text-sm text-muted-foreground mt-1">
                    Available: {{ $account->currency }} {{ number_format((float) $account->available_balance, 2) }}
                </p>
            @endif
            @if($account->credit_limit)
                <p class="text-sm text-muted-foreground mt-1">
                    Credit Limit: {{ $account->currency }} {{ number_format((float) $account->credit_limit, 2) }}
                </p>
            @endif
        </div>

        <!-- Payment Due Date & Interest Rate -->
        @if($account->hasPaymentDue() || $account->interest_rate)
            <div class="mb-4 space-y-2">
                @if($account->hasPaymentDue())
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-xs text-muted-foreground mb-1">Payment Due</p>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $account->due_date_color_class }}">
                                    {{ $account->formatted_due_date }}
                                </span>
                                @if($account->minimum_payment_amount || $account->next_payment_amount)
                                    <span class="text-xs text-muted-foreground">
                                        {{ $account->currency }} {{ number_format((float) ($account->next_payment_amount ?? $account->minimum_payment_amount), 2) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <button wire:click="openEditDueDate('{{ $account->id }}')" 
                                class="p-1.5 text-muted-foreground hover:text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150"
                                title="Edit Due Date">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    </div>
                @endif
                @if($account->interest_rate)
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Interest Rate</p>
                        <p class="text-sm font-medium text-card-foreground">{{ $account->formatted_interest_rate }}</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Sync Status -->
        <div class="flex items-center justify-between">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $account->status_badge_color }}">
                {{ ucfirst($account->sync_status) }}
            </span>
            @if($account->last_synced_at)
                <p class="text-xs text-muted-foreground">
                    {{ $account->last_synced_at->diffForHumans() }}
                </p>
            @else
                <p class="text-xs text-muted-foreground">Never synced</p>
            @endif
        </div>
    </div>

    <!-- Expandable Details -->
    @if($this->isExpanded($account->id))
        <div class="border-t border-border p-6 space-y-4" x-transition>
            <!-- Account Details -->
            <div>
                <h5 class="text-sm font-semibold text-card-foreground mb-3">Account Details</h5>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Official Name</dt>
                        <dd class="text-card-foreground font-medium">{{ $account->official_name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Account Type</dt>
                        <dd class="text-card-foreground font-medium">{{ ucfirst(str_replace('_', ' ', $account->account_type)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Currency</dt>
                        <dd class="text-card-foreground font-medium">{{ $account->currency }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Last Synced</dt>
                        <dd class="text-card-foreground font-medium">
                            {{ $account->last_synced_at ? $account->last_synced_at->format('M d, Y H:i') : 'Never' }}
                        </dd>
                    </div>
                    @if($account->hasPaymentDue())
                        <div>
                            <dt class="text-muted-foreground">Payment Due</dt>
                            <dd class="text-card-foreground font-medium">
                                <span class="{{ $account->due_date_color_class }} px-2 py-0.5 rounded text-xs">
                                    {{ $account->formatted_due_date }}
                                </span>
                            </dd>
                        </div>
                    @endif
                    @if($account->interest_rate)
                        <div>
                            <dt class="text-muted-foreground">Interest Rate</dt>
                            <dd class="text-card-foreground font-medium">{{ $account->formatted_interest_rate }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Recent Transactions -->
            @php
                $recentTransactions = $account->transactions()
                    ->orderBy('transaction_date', 'desc')
                    ->limit(5)
                    ->get();
            @endphp
            @if($recentTransactions->count() > 0)
                <div>
                    <h5 class="text-sm font-semibold text-card-foreground mb-3">Recent Transactions</h5>
                    <div class="space-y-2">
                        @foreach($recentTransactions as $transaction)
                            <div class="flex items-center justify-between p-2 bg-muted rounded-[var(--radius-sm)]">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-card-foreground truncate">{{ $transaction->name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ $transaction->transaction_date->format('M d, Y') }}
                                    </p>
                                </div>
                                <p class="text-sm font-semibold text-card-foreground ml-4">
                                    {{ $transaction->formatted_amount }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('transactions.index', ['account' => $account->id]) }}" 
                       class="block mt-3 text-sm text-primary hover:underline text-center">
                        View All Transactions
                    </a>
                </div>
            @endif

            <!-- Quick Actions -->
            <div>
                <h5 class="text-sm font-semibold text-card-foreground mb-3">Quick Actions</h5>
                <div class="grid grid-cols-2 gap-2">
                    <button wire:click="refreshBalance('{{ $account->id }}')" 
                            class="px-4 py-2 bg-muted text-card-foreground rounded-[var(--radius-sm)] font-medium text-sm hover:bg-muted/80 transition-all duration-150">
                        Refresh Balance
                    </button>
                    <a href="{{ route('transactions.index', ['account' => $account->id]) }}" 
                       class="px-4 py-2 bg-muted text-card-foreground rounded-[var(--radius-sm)] font-medium text-sm hover:bg-muted/80 transition-all duration-150 text-center">
                        View Transactions
                    </a>
                    <button wire:click="editNickname('{{ $account->id }}')" 
                            class="px-4 py-2 bg-muted text-card-foreground rounded-[var(--radius-sm)] font-medium text-sm hover:bg-muted/80 transition-all duration-150">
                        Edit Nickname
                    </button>
                    <button wire:click="openEditDueDate('{{ $account->id }}')" 
                            class="px-4 py-2 bg-muted text-card-foreground rounded-[var(--radius-sm)] font-medium text-sm hover:bg-muted/80 transition-all duration-150">
                        {{ $account->hasPaymentDue() ? 'Edit Due Date' : 'Add Due Date' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

