<div class="space-y-6" x-data="{ plaidHandler: null }">
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

    @if (session()->has('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             x-init="setTimeout(() => show = false, 5000)"
             class="bg-destructive/20 border border-destructive text-destructive px-4 py-3 rounded-[var(--radius-md)] relative" 
             role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
            <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                <svg class="fill-current h-6 w-6 text-destructive" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </button>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Balance Card -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Total Balance</p>
            <p class="text-3xl font-semibold text-card-foreground mt-1">
                ${{ number_format($summaryStats['total_balance'], 2) }}
            </p>
            <p class="text-xs text-muted-foreground mt-1">Across all accounts</p>
        </div>

        <!-- Active Accounts Card -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Active Accounts</p>
            <p class="text-3xl font-semibold text-chart-2 mt-1">{{ $summaryStats['active_count'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">Synced and ready</p>
        </div>

        <!-- Syncing Card -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <p class="text-sm font-medium text-muted-foreground">Syncing</p>
            <p class="text-3xl font-semibold text-chart-4 mt-1">{{ $summaryStats['syncing_count'] }}</p>
            <p class="text-xs text-muted-foreground mt-1">In progress</p>
        </div>

        <!-- Link New Account Card -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6 flex flex-col justify-between">
            <div>
                <p class="text-sm font-medium text-muted-foreground">Total Accounts</p>
                <p class="text-3xl font-semibold text-card-foreground mt-1">{{ $summaryStats['total_count'] }}</p>
            </div>
            <button onclick="linkAccount()" 
                    class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Link Account
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Search -->
            <div class="flex-1">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search accounts, institutions..."
                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
            </div>

            <!-- Type Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto">
                <button wire:click="$set('typeFilter', 'all')" 
                        class="px-3 py-1.5 text-sm font-medium rounded-[var(--radius-sm)] transition-all duration-150 whitespace-nowrap {{ $typeFilter === 'all' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
                    All
                </button>
                <button wire:click="$set('typeFilter', 'checking')" 
                        class="px-3 py-1.5 text-sm font-medium rounded-[var(--radius-sm)] transition-all duration-150 whitespace-nowrap {{ $typeFilter === 'checking' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
                    Checking
                </button>
                <button wire:click="$set('typeFilter', 'savings')" 
                        class="px-3 py-1.5 text-sm font-medium rounded-[var(--radius-sm)] transition-all duration-150 whitespace-nowrap {{ $typeFilter === 'savings' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
                    Savings
                </button>
                <button wire:click="$set('typeFilter', 'credit_card')" 
                        class="px-3 py-1.5 text-sm font-medium rounded-[var(--radius-sm)] transition-all duration-150 whitespace-nowrap {{ $typeFilter === 'credit_card' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
                    Credit Cards
                </button>
                <button wire:click="$set('typeFilter', 'investment')" 
                        class="px-3 py-1.5 text-sm font-medium rounded-[var(--radius-sm)] transition-all duration-150 whitespace-nowrap {{ $typeFilter === 'investment' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
                    Investments
                </button>
                <button wire:click="$set('typeFilter', 'loan')" 
                        class="px-3 py-1.5 text-sm font-medium rounded-[var(--radius-sm)] transition-all duration-150 whitespace-nowrap {{ $typeFilter === 'loan' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
                    Loans
                </button>
            </div>

            <!-- Status Filter -->
            <select wire:model.live="statusFilter" 
                    class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="syncing">Syncing</option>
                <option value="failed">Failed</option>
            </select>

            <!-- Group Toggle -->
            <button wire:click="toggleGrouping" 
                    class="px-4 py-2 bg-muted text-muted-foreground rounded-[var(--radius-sm)] font-medium text-sm hover:bg-muted/80 transition-all duration-150 {{ $groupByInstitution ? 'bg-primary text-primary-foreground' : '' }}">
                <span class="hidden lg:inline">{{ $groupByInstitution ? 'Ungroup' : 'Group' }}</span>
                <span class="lg:hidden">{{ $groupByInstitution ? 'Ungroup' : 'Group' }}</span>
            </button>
        </div>
    </div>

    <!-- Accounts Grid -->
    @if($accounts->isEmpty())
        <!-- Empty State -->
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <h3 class="mt-4 text-lg font-semibold text-card-foreground">No accounts found</h3>
            <p class="mt-2 text-sm text-muted-foreground">
                @if($search || $typeFilter !== 'all' || $statusFilter !== 'all')
                    Try adjusting your filters or search terms.
                @else
                    Get started by linking your first bank account.
                @endif
            </p>
            @if(!$search && $typeFilter === 'all' && $statusFilter === 'all')
                <div class="mt-6">
                    <button onclick="linkAccount()" 
                            class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Link Your First Account
                    </button>
                </div>
            @endif
        </div>
    @else
        @if($groupByInstitution)
            <!-- Grouped by Institution -->
            @foreach($groupedAccounts as $institution => $institutionAccounts)
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-card-foreground">{{ $institution }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($institutionAccounts as $account)
                            @include('livewire.accounts.partials.account-card', ['account' => $account])
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <!-- Flat Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($accounts as $account)
                    @include('livewire.accounts.partials.account-card', ['account' => $account])
                @endforeach
            </div>
        @endif
    @endif

    <!-- Edit Nickname Modal -->
    @if($showNicknameModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-show="$wire.showNicknameModal" x-transition>
            <div class="bg-card border border-border rounded-[var(--radius-large)] shadow-elevated p-6 w-full max-w-md" @click.away="$wire.closeNicknameModal()">
                <h3 class="text-lg font-semibold text-card-foreground mb-4">Edit Account Nickname</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Nickname</label>
                        <input type="text" 
                               wire:model="nickname" 
                               placeholder="Enter custom name..."
                               class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        <p class="mt-1 text-xs text-muted-foreground">Leave empty to use default account name</p>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <button wire:click="closeNicknameModal" 
                                class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors">
                            Cancel
                        </button>
                        <button wire:click="saveNickname" 
                                class="px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Disconnect Confirmation Modal -->
    @if($showDisconnectModal && $selectedAccount)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-show="$wire.showDisconnectModal" x-transition>
            <div class="bg-card border border-border rounded-[var(--radius-large)] shadow-elevated p-6 w-full max-w-md" @click.away="$wire.closeDisconnectModal()">
                <h3 class="text-lg font-semibold text-card-foreground mb-4">Disconnect Account</h3>
                <p class="text-sm text-muted-foreground mb-6">
                    Are you sure you want to disconnect <strong>{{ $selectedAccount->display_name_without_mask }}</strong>? 
                    This will stop syncing transactions and remove the account from your dashboard.
                </p>
                <div class="flex items-center justify-end gap-3">
                    <button wire:click="closeDisconnectModal" 
                            class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors">
                        Cancel
                    </button>
                    <button wire:click="disconnectAccount" 
                            class="px-4 py-2 bg-destructive text-destructive-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                        Disconnect
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Due Date Modal -->
    @if($showDueDateModal && $selectedAccount)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-show="$wire.showDueDateModal" x-transition>
            <div class="bg-card border border-border rounded-[var(--radius-large)] shadow-elevated p-6 w-full max-w-md max-h-[90vh] overflow-y-auto" @click.away="$wire.closeDueDateModal()">
                <h3 class="text-lg font-semibold text-card-foreground mb-4">Edit Due Date & Payment Info</h3>
                <div class="space-y-4">
                    <!-- Payment Due Date -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Payment Due Date</label>
                        <input type="date" 
                               wire:model="paymentDueDate" 
                               class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        <p class="mt-1 text-xs text-muted-foreground">Leave empty to remove due date</p>
                    </div>

                    <!-- Payment Due Day (Recurring) -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Recurring Day of Month (Optional)</label>
                        <input type="number" 
                               wire:model="paymentDueDay" 
                               min="1" 
                               max="31"
                               placeholder="e.g., 15"
                               class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        <p class="mt-1 text-xs text-muted-foreground">Day of month when payment is typically due (1-31)</p>
                    </div>

                    <!-- Payment Amount -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">
                            Payment Amount ({{ $selectedAccount->currency }})
                        </label>
                        <input type="number" 
                               wire:model="paymentAmount" 
                               step="0.01"
                               min="0"
                               placeholder="0.00"
                               class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        <p class="mt-1 text-xs text-muted-foreground">
                            @if($selectedAccount->account_type === 'credit_card')
                                Minimum payment amount
                            @else
                                Next payment amount
                            @endif
                        </p>
                    </div>

                    <!-- Interest Rate -->
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Interest Rate (%)</label>
                        <div class="flex gap-2">
                            <input type="number" 
                                   wire:model="interestRate" 
                                   step="0.01"
                                   min="0"
                                   max="100"
                                   placeholder="0.00"
                                   class="flex-1 px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                            <select wire:model="interestRateType" 
                                    class="px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                                <option value="">Type</option>
                                <option value="fixed">Fixed</option>
                                <option value="variable">Variable</option>
                            </select>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Annual Percentage Rate (APR)</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                        <button wire:click="closeDueDateModal" 
                                class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors">
                            Cancel
                        </button>
                        <button wire:click="saveDueDate" 
                                class="px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Plaid Link Script -->
<script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
<script>
    // Check if we're returning from OAuth redirect
    // Plaid may redirect to its own OAuth page or our callback
    const urlParams = new URLSearchParams(window.location.search);
    const hashParams = new URLSearchParams(window.location.hash.substring(1));
    
    // Check both query params and hash for OAuth state
    const oauthStateId = urlParams.get('oauth_state_id') || hashParams.get('oauth_state_id');
    const oauthError = urlParams.get('error') || hashParams.get('error');
    const oauthCode = hashParams.get('code'); // OAuth code is in hash
    
    // If we're on Plaid's OAuth page, extract state from hash and redirect to our callback
    if (window.location.hostname === 'cdn.plaid.com' && oauthCode) {
        // Extract state from hash
        const stateParam = hashParams.get('state');
        if (stateParam) {
            try {
                // State is base64 encoded JSON
                const stateData = JSON.parse(atob(stateParam));
                const oauthStateIdFromState = stateData.oauth_state_id;
                
                // Redirect to our callback with the OAuth state ID
                const callbackUrl = '{{ route('accounts.oauth-callback') }}?oauth_state_id=' + encodeURIComponent(oauthStateIdFromState);
                window.location.href = callbackUrl;
            } catch (e) {
                console.error('Failed to parse OAuth state:', e);
                // Fallback: redirect to accounts page
                window.location.href = '{{ route('accounts.index') }}';
            }
        } else {
            window.location.href = '{{ route('accounts.index') }}';
        }
    }
    
    // If we have OAuth state on our domain, we need to complete the OAuth flow
    if (oauthStateId && !oauthError && window.location.hostname !== 'cdn.plaid.com') {
        // Get the stored link token from sessionStorage
        const storedLinkToken = sessionStorage.getItem('plaid_link_token');
        
        if (storedLinkToken) {
            // Reinitialize Plaid Link with the stored token to complete OAuth
            const plaidHandler = Plaid.create({
                token: storedLinkToken,
                receivedRedirectUri: window.location.href, // Pass the current URL with OAuth params
                onSuccess: async function(public_token, metadata) {
                    // Clear stored token
                    sessionStorage.removeItem('plaid_link_token');
                    
                    // Exchange public token for access token
                    try {
                        const exchangeResponse = await fetch('{{ route('accounts.exchange-token') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                public_token: public_token,
                                metadata: metadata
                            })
                        });

                        const exchangeData = await exchangeResponse.json();

                        if (!exchangeResponse.ok) {
                            throw new Error(exchangeData.error || 'Failed to link account');
                        }

                        // Success! Reload page to show new account and clean URL
                        window.location.href = '{{ route('accounts.index') }}';
                    } catch (error) {
                        console.error('Token exchange error:', error);
                        alert('Failed to link account: ' + error.message);
                        window.location.href = '{{ route('accounts.index') }}';
                    }
                },
                onExit: function(err, metadata) {
                    // Clear stored token
                    sessionStorage.removeItem('plaid_link_token');
                    
                    // Clean URL and redirect
                    window.location.href = '{{ route('accounts.index') }}';
                    
                    if (err != null) {
                        console.error('Plaid Link error:', err);
                        alert('An error occurred: ' + err.error_message);
                    }
                },
                onEvent: function(eventName, metadata) {
                    console.log('Plaid event:', eventName, metadata);
                }
            });

            // Open Plaid Link to complete OAuth flow
            plaidHandler.open();
        } else {
            // No stored token, redirect to accounts page
            window.location.href = '{{ route('accounts.index') }}';
        }
    }
    
    async function linkAccount() {
        try {
            // Get link token from backend
            const response = await fetch('{{ route('accounts.link-token') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (!response.ok || data.error) {
                throw new Error(data.error || 'Failed to initialize Plaid');
            }

            // Store link token in sessionStorage for OAuth redirect handling
            sessionStorage.setItem('plaid_link_token', data.link_token);

            // Initialize Plaid Link
            const plaidHandler = Plaid.create({
                token: data.link_token,
                onSuccess: async function(public_token, metadata) {
                    // Clear stored token
                    sessionStorage.removeItem('plaid_link_token');
                    
                    // Exchange public token for access token
                    try {
                        const exchangeResponse = await fetch('{{ route('accounts.exchange-token') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                public_token: public_token,
                                metadata: metadata
                            })
                        });

                        const exchangeData = await exchangeResponse.json();

                        if (!exchangeResponse.ok) {
                            throw new Error(exchangeData.error || 'Failed to link account');
                        }

                        // Success! Reload page to show new account
                        window.location.reload();
                    } catch (error) {
                        console.error('Token exchange error:', error);
                        alert('Failed to link account: ' + error.message);
                    }
                },
                onExit: function(err, metadata) {
                    // Clear stored token
                    sessionStorage.removeItem('plaid_link_token');
                    
                    if (err != null) {
                        console.error('Plaid Link error:', err);
                        alert('An error occurred: ' + err.error_message);
                    }
                },
                onEvent: function(eventName, metadata) {
                    console.log('Plaid event:', eventName, metadata);
                    
                    // If OAuth flow is initiated, store the link token
                    if (eventName === 'TRANSITION_VIEW' && metadata.view_name === 'OAUTH') {
                        // Token is already stored, but ensure it's there
                        sessionStorage.setItem('plaid_link_token', data.link_token);
                    }
                }
            });

            // Open Plaid Link
            plaidHandler.open();

        } catch (error) {
            console.error('Plaid Link initialization error:', error);
            alert('Failed to start account linking: ' + error.message);
            sessionStorage.removeItem('plaid_link_token');
        }
    }
</script>
