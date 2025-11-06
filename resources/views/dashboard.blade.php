<x-layouts.app>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="pageTitle">Dashboard</x-slot>

    <div class="space-y-6">
        <!-- Welcome Card -->
        <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-card-foreground mb-4">
                    Welcome to Unobtuse Ledger!
                </h2>
                <p class="text-muted-foreground mb-4">
                    You're logged in! This is your dashboard where you'll manage your bills, track spending, and view AI-powered insights.
                </p>
                
                @if (auth()->user()->isOAuthUser())
                    <div class="bg-chart-3/10 border border-chart-3/30 rounded-lg p-4 mb-4">
                        <p class="text-sm text-chart-3 dark:text-chart-3">
                            ✓ You're signed in with Google OAuth
                        </p>
                    </div>
                @endif

                @if (auth()->user()->hasTwoFactorEnabled())
                    <div class="bg-chart-2/10 border border-chart-2/30 rounded-lg p-4 mb-4">
                        <p class="text-sm text-chart-2 dark:text-chart-2">
                            ✓ Two-factor authentication is enabled
                        </p>
                    </div>
                @endif

                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-card-foreground mb-3">Phase 1 Complete ✅</h3>
                    <ul class="space-y-2 text-sm text-muted-foreground">
                        <li>✓ Plaid integration configured</li>
                        <li>✓ Account linking ready</li>
                        <li>✓ Transaction sync background jobs</li>
                        <li>✓ Bill detection algorithm</li>
                        <li>✓ Database schema complete</li>
                        <li>✓ Webhook handler implemented</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Linked Accounts -->
        <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-card-foreground">Linked Accounts</h3>
                    <a href="{{ route('accounts.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:opacity-90 transition-all duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Link Bank Account
                    </a>
                </div>

                @if (auth()->user()->accounts->isEmpty())
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <h3 class="mt-4 text-sm font-medium text-card-foreground">No accounts linked</h3>
                        <p class="mt-2 text-sm text-muted-foreground">Get started by linking your first bank account.</p>
                        <div class="mt-6">
                            <a href="{{ route('accounts.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:opacity-90 transition-all duration-150">
                                Link Your First Account
                            </a>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach (auth()->user()->accounts as $account)
                            <div class="border border-border rounded-lg p-4 hover:shadow-elevated transition-shadow bg-muted">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold text-card-foreground">{{ $account->account_name }}</h4>
                                    @if ($account->sync_status === 'synced')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-chart-2/20 text-chart-2">
                                            Synced
                                        </span>
                                    @elseif ($account->sync_status === 'syncing')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-chart-4/20 text-chart-4">
                                            Syncing...
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-destructive/20 text-destructive">
                                            Error
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-muted-foreground mb-1">{{ $account->institution_name }}</p>
                                <p class="text-sm text-muted-foreground mb-3">
                                    {{ ucfirst($account->account_type) }} •••• {{ $account->mask }}
                                </p>
                                <div class="text-2xl font-semibold text-card-foreground">
                                    {{ $account->formatted_balance }}
                                </div>
                                @if ($account->last_synced_at)
                                    <p class="text-xs text-muted-foreground mt-2">
                                        Last synced {{ $account->last_synced_at->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-layouts.app>

