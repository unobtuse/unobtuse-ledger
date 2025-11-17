<div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm overflow-hidden hover:shadow-elevated transition-all duration-150" 
     x-data="{ showActions: false }">
    <!-- Card Header with Institution Info -->
    <div class="p-6 pb-4 border-b border-border/50">
        <div class="flex items-start justify-between mb-3">
            <!-- Institution and Name -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                    <!-- Institution Badge/Logo Fallback -->
                    @if($account->institution && $account->institution->logo_url)
                        <img src="{{ $account->institution->logo_url }}" 
                             alt="{{ $account->institution->name }} logo"
                             class="w-6 h-6 object-contain rounded-sm"
                             onerror="this.style.display='none'">
                    @else
                        <!-- Institution Initials Fallback -->
                        <div class="w-6 h-6 rounded-sm bg-muted flex items-center justify-center text-xs font-bold text-muted-foreground">
                            {{ substr(str_replace(' ', '', $account->institution_name), 0, 1) }}
                        </div>
                    @endif
                    
                    <!-- Institution Name -->
                    <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                        {{ $account->institution_name }}
                    </span>
                </div>
                
                <!-- Account Name -->
                <h4 class="text-lg font-semibold text-card-foreground truncate">
                    {{ $account->display_name_without_mask }}
                </h4>
                
                <!-- Last 4 & Type -->
                <div class="flex items-center gap-2 mt-2">
                @if($account->mask)
                        <span class="text-xs text-muted-foreground font-mono">•••• {{ $account->mask }}</span>
                @endif
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-muted/50 text-muted-foreground">
                        {{ ucfirst(str_replace('_', ' ', $account->account_type)) }}
                    </span>
                </div>
            </div>
            
            <!-- Action Menu Button -->
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
                        <button wire:click="syncAccount('{{ $account->id }}')" 
                                class="w-full text-left px-4 py-2 text-sm text-popover-foreground hover:bg-muted transition-colors">
                            Sync Transactions
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
                        @if(!$account->institution_url)
                            <button wire:click="openEditWebsiteUrl('{{ $account->id }}')" 
                                    class="w-full text-left px-4 py-2 text-sm text-popover-foreground hover:bg-muted transition-colors">
                                Add Website URL
                            </button>
                        @else
                            <button wire:click="openEditWebsiteUrl('{{ $account->id }}')" 
                                    class="w-full text-left px-4 py-2 text-sm text-popover-foreground hover:bg-muted transition-colors">
                                Edit Website URL
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
        </div>

    <!-- Card Body - Main Balance Section -->
    <div class="p-6 space-y-4" wire:click="toggleExpand('{{ $account->id }}')">
        <!-- Primary Balance Display -->
        <div class="cursor-pointer">
            <p class="text-xs font-medium text-muted-foreground mb-1 uppercase tracking-wide">
                @if($account->account_type === 'credit_card')
                    Amount Owed
                @else
                    Available Balance
                @endif
            </p>
            <p class="text-5xl font-bold text-card-foreground">
                {{ $account->formatted_balance }}
            </p>
        </div>

        <!-- Secondary Balance Info (Credit Cards) -->
        @if($account->account_type === 'credit_card')
            <div class="grid grid-cols-2 gap-3 pt-2">
                @if($account->available_balance !== null)
                    <div class="bg-muted/30 rounded-[var(--radius-md)] p-3">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Available Credit</p>
                        <p class="text-lg font-semibold text-card-foreground">
                            {{ $account->currency }} {{ number_format((float) $account->available_balance, 2) }}
                </p>
                    </div>
            @endif
            @if($account->credit_limit)
                    <div class="bg-muted/30 rounded-[var(--radius-md)] p-3">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Credit Limit</p>
                        <p class="text-lg font-semibold text-card-foreground">
                            {{ $account->currency }} {{ number_format((float) $account->credit_limit, 2) }}
                </p>
                    </div>
            @endif
                @if($account->interest_rate)
                    <div class="bg-muted/30 rounded-[var(--radius-md)] p-3">
                        <p class="text-xs font-medium text-muted-foreground mb-1">APR</p>
                        <p class="text-lg font-semibold text-card-foreground">
                            {{ number_format((float) $account->interest_rate, 2) }}%
                        </p>
        </div>
                @endif
                @if($account->hasPaymentDue())
                    <div class="bg-muted/30 rounded-[var(--radius-md)] p-3">
                        <p class="text-xs font-medium text-muted-foreground mb-1">Min. Payment</p>
                        <p class="text-lg font-semibold text-card-foreground">
                                        {{ $account->currency }} {{ number_format((float) ($account->next_payment_amount ?? $account->minimum_payment_amount), 2) }}
                        </p>
                    </div>
                                @endif
                            </div>
        @endif

        <!-- Payment Due Information -->
        @if($account->hasPaymentDue())
            <div class="p-3 rounded-[var(--radius-md)] {{ $account->due_date_status === 'overdue' ? 'bg-destructive/10' : ($account->due_date_status === 'warning' ? 'bg-chart-4/10' : 'bg-muted/30') }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted-foreground mb-1">Payment Due</p>
                        <p class="text-sm font-semibold {{ $account->due_date_status === 'overdue' ? 'text-destructive' : ($account->due_date_status === 'warning' ? 'text-chart-4' : 'text-card-foreground') }}">
                            {{ $account->formatted_due_date }}
                        </p>
                        </div>
                        <button wire:click="openEditDueDate('{{ $account->id }}')" 
                            @click.stop
                                class="p-1.5 text-muted-foreground hover:text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150"
                                title="Edit Due Date">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    </div>
            </div>
        @endif

        <!-- Sync Status -->
        <div class="flex items-center justify-between pt-2 border-t border-border/50">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $account->status_badge_color }}">
                {{ ucfirst($account->sync_status) }}
            </span>
            @if($account->last_synced_at)
                <p class="text-xs text-muted-foreground">
                    Synced {{ $account->last_synced_at->diffForHumans() }}
                </p>
            @else
                <p class="text-xs text-muted-foreground">Never synced</p>
            @endif
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="px-6 pb-6" @click.stop>
        @if($account->institution_url)
            <a href="{{ $account->institution_url }}" 
               target="_blank"
               rel="noopener noreferrer"
               class="block w-full px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-medium text-sm hover:bg-primary/90 transition-all duration-150 text-center">
                🌐 Go to Website →
            </a>
        @else
            <button wire:click="openEditWebsiteUrl('{{ $account->id }}')"
                    class="w-full px-4 py-2 bg-muted text-muted-foreground rounded-[var(--radius-md)] font-medium text-sm hover:bg-muted/80 transition-all duration-150">
                + Add Website URL
            </button>
        @endif
    </div>

    <!-- Expandable Details Section -->
    @if($this->isExpanded($account->id))
        <div class="border-t border-border bg-muted/20 p-6 space-y-4" x-transition>
            <!-- Account Details -->
            <div>
                <h5 class="text-sm font-semibold text-card-foreground mb-3">Account Details</h5>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-muted-foreground text-xs mb-1">Official Name</dt>
                        <dd class="text-card-foreground font-medium">{{ $account->official_name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-xs mb-1">Currency</dt>
                        <dd class="text-card-foreground font-medium">{{ $account->currency }}</dd>
                    </div>
                    @if($account->hasPaymentDue())
                        <div class="col-span-2">
                            <dt class="text-muted-foreground text-xs mb-1">Last Synced</dt>
                            <dd class="text-card-foreground font-medium">
                                {{ $account->last_synced_at ? $account->last_synced_at->format('M d, Y H:i') : 'Never' }}
                            </dd>
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
                            <div class="flex items-center justify-between p-2 bg-card rounded-[var(--radius-sm)] border border-border/50">
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
                        View All Transactions →
                    </a>
                </div>
            @else
                <div class="text-center py-4">
                    <p class="text-sm text-muted-foreground mb-3">No transactions yet</p>
                    <button wire:click="syncAccount('{{ $account->id }}')" 
                            @click.stop
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-medium text-sm hover:bg-primary/90 transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sync Transactions
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
