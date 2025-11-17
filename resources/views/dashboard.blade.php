<x-layouts.app>
    <x-slot name="title">Financial Dashboard</x-slot>
    <x-slot name="pageTitle">Dashboard</x-slot>

    @if (auth()->user()->accounts->isEmpty())
        {{-- Show welcome message if no accounts linked --}}
        <div class="space-y-6">
            <div class="bg-card border border-border rounded-lg shadow-sm overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-card-foreground mb-4">
                        Welcome to Unobtuse Ledger!
                    </h2>
                    <p class="text-muted-foreground mb-4">
                        Get started by linking your first bank account to see your comprehensive financial dashboard.
                    </p>

                    <div class="text-center py-12">
                        <svg class="mx-auto h-16 w-16 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-card-foreground">No accounts linked</h3>
                        <p class="mt-2 text-sm text-muted-foreground">Link your bank account to unlock powerful financial insights.</p>
                        <div class="mt-6">
                            <a href="{{ route('accounts.index') }}"
                               class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:opacity-90 transition-all duration-150">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Link Your First Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Show comprehensive financial dashboard --}}
        <livewire:dashboard.financial-overview />
    @endif

</x-layouts.app>

