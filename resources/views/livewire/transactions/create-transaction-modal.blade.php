<div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show" @keydown.escape.window="show = false; $wire.close()">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="show" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-black bg-opacity-50" 
             @click="show = false; $wire.close()"></div>

        <!-- Modal panel -->
        <div x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block w-full max-w-lg my-8 overflow-hidden text-left align-middle transition-all transform bg-card border border-border rounded-lg shadow-elevated">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-card-foreground">Add Transaction</h3>
                    <button wire:click="close" 
                            class="text-muted-foreground hover:text-card-foreground transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">
                        Account <span class="text-destructive">*</span>
                    </label>
                    <select wire:model="accountId" 
                            class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">Select an account...</option>
                        @foreach($this->accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_name }} ({{ $account->formatted_balance }})</option>
                        @endforeach
                    </select>
                    @error('accountId') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">
                        Merchant Name <span class="text-destructive">*</span>
                    </label>
                    <input type="text" 
                           wire:model="merchantName"
                           placeholder="e.g., Amazon, Walmart, Starbucks"
                           class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    @error('merchantName') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">
                        Description
                    </label>
                    <input type="text" 
                           wire:model="description"
                           placeholder="Optional description"
                           class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    @error('description') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">
                            Amount <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-muted-foreground">$</span>
                            <input type="number" 
                                   wire:model="amount"
                                   step="0.01"
                                   min="0.01"
                                   placeholder="0.00"
                                   class="w-full pl-7 pr-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Enter positive for expenses, negative for income</p>
                        @error('amount') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">
                            Date <span class="text-destructive">*</span>
                        </label>
                        <input type="date" 
                               wire:model="transactionDate"
                               max="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                        @error('transactionDate') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-2">
                        Category
                    </label>
                    <select wire:model="category" 
                            wire:change="$set('useCustomCategory', false)"
                            class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">Choose a category...</option>
                        @foreach($existingCategories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex-1 border-t border-border"></div>
                    <span class="text-sm text-muted-foreground">OR</span>
                    <div class="flex-1 border-t border-border"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-2">
                        Custom Category
                    </label>
                    <input type="text" 
                           wire:model="customCategory"
                           wire:change="$set('useCustomCategory', true)"
                           placeholder="e.g., Groceries, Rent, Utilities..."
                           class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" 
                           wire:model="isRecurring"
                           id="isRecurring"
                           class="w-4 h-4 text-primary bg-background border-input rounded focus:ring-ring">
                    <label for="isRecurring" class="text-sm text-muted-foreground">
                        This is a recurring transaction
                    </label>
                </div>

                @if($isRecurring)
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">
                            Frequency
                        </label>
                        <select wire:model="recurringFrequency" 
                                class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Biweekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                        </select>
                        @error('recurringFrequency') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">
                        Notes
                    </label>
                    <textarea wire:model="notes"
                              rows="3"
                              placeholder="Optional notes..."
                              class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-border bg-muted flex items-center justify-end gap-2">
                <button wire:click="close" 
                        class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground border border-border rounded-lg hover:bg-background transition-colors">
                    Cancel
                </button>
                <button wire:click="save" 
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-lg hover:opacity-90 transition-all duration-150 disabled:opacity-50">
                    <span wire:loading.remove>Create Transaction</span>
                    <span wire:loading>Creating...</span>
                </button>
            </div>
        </div>
    </div>
</div>

