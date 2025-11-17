<x-layouts.app>
    <x-slot name="title">Accounts</x-slot>
    <x-slot name="pageTitle">Accounts</x-slot>

    <div x-data="{ activeTab: 'list' }" class="space-y-6">
        <!-- Tabs -->
        <div class="border-b border-border">
            <nav class="flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'list'" 
                        :class="activeTab === 'list' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-card-foreground hover:border-muted-foreground'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors duration-150">
                    Account List
                </button>
                <button @click="activeTab = 'analytics'" 
                        :class="activeTab === 'analytics' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-card-foreground hover:border-muted-foreground'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors duration-150">
                    Analytics
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div x-show="activeTab === 'list'" x-transition>
            @livewire('accounts.accounts-list')
        </div>
        <div x-show="activeTab === 'analytics'" x-transition>
            @livewire('accounts.accounts-analytics')
        </div>
    </div>
</x-layouts.app>

