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
             class="inline-block w-full max-w-md my-8 overflow-hidden text-left align-middle transition-all transform bg-card border border-border rounded-lg shadow-elevated">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-border">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-card-foreground">Edit Category</h3>
                    <button wire:click="close" 
                            class="text-muted-foreground hover:text-card-foreground transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-2">
                        Select from existing categories
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
                        Enter custom category
                    </label>
                    <input type="text" 
                           wire:model="customCategory"
                           wire:change="$set('useCustomCategory', true)"
                           placeholder="e.g., Groceries, Rent, Utilities..."
                           class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" 
                           wire:model="useCustomCategory"
                           id="useCustom"
                           class="w-4 h-4 text-primary bg-background border-input rounded focus:ring-ring">
                    <label for="useCustom" class="text-sm text-muted-foreground">
                        Use custom category
                    </label>
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
                    <span wire:loading.remove>Save Category</span>
                    <span wire:loading>Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>


