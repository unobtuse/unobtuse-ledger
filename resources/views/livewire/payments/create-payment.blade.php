<div>
    @if($showModal)
        <!-- Modal Overlay -->
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" 
             x-data 
             x-on:click.away="$wire.closeModal()">
            <div class="bg-card border border-border rounded-lg shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-card-foreground">Create Payment</h3>
                        <button wire:click="closeModal" class="text-muted-foreground hover:text-card-foreground">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @if($error)
                        <div class="mb-4 p-4 bg-destructive/10 border border-destructive/20 text-destructive rounded-lg">
                            <p>{{ $error }}</p>
                        </div>
                    @endif

                    @if($success)
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                            <p>{{ $success }}</p>
                        </div>
                    @endif

                    <form wire:submit="createPayment" class="space-y-4">
                        <!-- Account Selection -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">From Account</label>
                            <select wire:model="selectedAccountId" class="w-full px-3 py-2 border border-border rounded-lg bg-background text-card-foreground">
                                <option value="">Select an account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->institution_name }} - {{ $account->account_name }}
                                        @if($account->mask)
                                            (••••{{ $account->mask }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('selectedAccountId') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>

                        <!-- Recipient Name -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Recipient Name</label>
                            <input type="text" wire:model="recipientName" 
                                   class="w-full px-3 py-2 border border-border rounded-lg bg-background text-card-foreground"
                                   placeholder="Enter recipient name">
                            @error('recipientName') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>

                        <!-- Account Number -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Account Number</label>
                            <input type="text" wire:model="recipientAccountNumber" 
                                   class="w-full px-3 py-2 border border-border rounded-lg bg-background text-card-foreground"
                                   placeholder="Enter account number">
                            @error('recipientAccountNumber') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>

                        <!-- Routing Number -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Routing Number</label>
                            <input type="text" wire:model="recipientRoutingNumber" 
                                   class="w-full px-3 py-2 border border-border rounded-lg bg-background text-card-foreground"
                                   placeholder="Enter 9-digit routing number" maxlength="9">
                            @error('recipientRoutingNumber') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Amount</label>
                            <input type="number" wire:model="amount" step="0.01" min="0.01"
                                   class="w-full px-3 py-2 border border-border rounded-lg bg-background text-card-foreground"
                                   placeholder="0.00">
                            @error('amount') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>

                        <!-- Memo -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Memo (Optional)</label>
                            <textarea wire:model="memo" rows="2"
                                      class="w-full px-3 py-2 border border-border rounded-lg bg-background text-card-foreground"
                                      placeholder="Add a note about this payment"></textarea>
                            @error('memo') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>

                        <!-- Scheduled Date -->
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Schedule Payment (Optional)</label>
                            <input type="date" wire:model="scheduledDate" 
                                   class="w-full px-3 py-2 border border-border rounded-lg bg-background text-card-foreground"
                                   min="{{ date('Y-m-d') }}">
                            @error('scheduledDate') <span class="text-sm text-destructive">{{ $message }}</span> @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4">
                            <button type="button" wire:click="closeModal" 
                                    class="px-4 py-2 text-card-foreground bg-muted rounded-lg hover:bg-muted/80 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                                Create Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
