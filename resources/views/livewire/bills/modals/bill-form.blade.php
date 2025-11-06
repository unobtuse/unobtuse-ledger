@if($showCreateModal || $showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" 
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
                    <h3 class="text-xl font-semibold text-card-foreground">
                        {{ $showEditModal ? 'Edit Bill' : 'Add New Bill' }}
                    </h3>
                    <button wire:click="closeModal" 
                            class="p-2 text-muted-foreground hover:text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="save" class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Bill Name *</label>
                        <input type="text" 
                               wire:model="name" 
                               required
                               class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        @error('name') 
                            <span class="text-sm text-destructive mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Description</label>
                        <textarea wire:model="description" 
                                  rows="3"
                                  class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150"></textarea>
                        @error('description') 
                            <span class="text-sm text-destructive mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Amount and Currency -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Amount *</label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0"
                                   wire:model="amount" 
                                   required
                                   class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                            @error('amount') 
                                <span class="text-sm text-destructive mt-1">{{ $message }}</span> 
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Currency</label>
                            <select wire:model="currency"
                                    class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="CAD">CAD</option>
                            </select>
                        </div>
                    </div>

                    <!-- Due Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Due Date *</label>
                            <input type="date" 
                                   wire:model="due_date" 
                                   required
                                   class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                            @error('due_date') 
                                <span class="text-sm text-destructive mt-1">{{ $message }}</span> 
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Next Due Date *</label>
                            <input type="date" 
                                   wire:model="next_due_date" 
                                   required
                                   class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                            @error('next_due_date') 
                                <span class="text-sm text-destructive mt-1">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>

                    <!-- Frequency -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Frequency *</label>
                            <select wire:model="frequency"
                                    class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Biweekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                                <option value="custom">Custom</option>
                            </select>
                            @error('frequency') 
                                <span class="text-sm text-destructive mt-1">{{ $message }}</span> 
                            @enderror
                        </div>
                        @if($frequency === 'custom')
                            <div>
                                <label class="block text-sm font-medium text-muted-foreground mb-1">Days</label>
                                <input type="number" 
                                       wire:model="frequency_value" 
                                       min="1"
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                            </div>
                        @endif
                    </div>

                    <!-- Category and Priority -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Category</label>
                            <select wire:model="category"
                                    class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                                <option value="">Select category</option>
                                <option value="rent">Rent</option>
                                <option value="mortgage">Mortgage</option>
                                <option value="utilities">Utilities</option>
                                <option value="internet">Internet</option>
                                <option value="phone">Phone</option>
                                <option value="insurance">Insurance</option>
                                <option value="subscription">Subscription</option>
                                <option value="loan">Loan</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Priority *</label>
                            <select wire:model="priority"
                                    class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Payment Status *</label>
                        <select wire:model="payment_status"
                                class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                            <option value="upcoming">Upcoming</option>
                            <option value="due">Due</option>
                            <option value="overdue">Overdue</option>
                            <option value="paid">Paid</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>

                    <!-- Autopay Settings -->
                    <div class="p-4 bg-muted rounded-[var(--radius-default)] space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-medium text-card-foreground">Enable Autopay</label>
                                <p class="text-xs text-muted-foreground mt-1">Automatically pay this bill</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       wire:model="is_autopay" 
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-muted-foreground/30 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-ring rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>
                        @if($is_autopay)
                            <div>
                                <label class="block text-sm font-medium text-muted-foreground mb-1">Autopay Account</label>
                                <input type="text" 
                                       wire:model="autopay_account" 
                                       placeholder="Account name or last 4 digits"
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                            </div>
                        @endif
                    </div>

                    <!-- Payment Link and Payee -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Payment Link</label>
                            <input type="url" 
                                   wire:model="payment_link" 
                                   placeholder="https://..."
                                   class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Payee Name</label>
                            <input type="text" 
                                   wire:model="payee_name" 
                                   placeholder="Company or person name"
                                   class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                        </div>
                    </div>

                    <!-- Reminder Settings -->
                    <div class="p-4 bg-muted rounded-[var(--radius-default)] space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-medium text-card-foreground">Enable Reminders</label>
                                <p class="text-xs text-muted-foreground mt-1">Get notified before bill is due</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       wire:model="reminder_enabled" 
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-muted-foreground/30 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-ring rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>
                        @if($reminder_enabled)
                            <div>
                                <label class="block text-sm font-medium text-muted-foreground mb-1">Remind Me (days before)</label>
                                <input type="number" 
                                       wire:model="reminder_days_before" 
                                       min="0" 
                                       max="30"
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150">
                            </div>
                        @endif
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Notes</label>
                        <textarea wire:model="notes" 
                                  rows="3"
                                  placeholder="Additional notes about this bill..."
                                  class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-all duration-150"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t border-border">
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-medium hover:opacity-90 transition-all duration-150">
                            {{ $showEditModal ? 'Update' : 'Create' }} Bill
                        </button>
                        <button type="button" 
                                wire:click="closeModal"
                                class="px-4 py-2 border border-border text-card-foreground hover:bg-muted rounded-[var(--radius-md)] transition-all duration-150">
                            Cancel
                        </button>
                        @if($showEditModal && $selectedBillId)
                            <button type="button" 
                                    wire:click="delete('{{ $selectedBillId }}')"
                                    wire:confirm="Are you sure you want to delete this bill?"
                                    class="px-4 py-2 bg-destructive text-destructive-foreground rounded-[var(--radius-md)] hover:opacity-90 transition-all duration-150">
                                Delete
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

