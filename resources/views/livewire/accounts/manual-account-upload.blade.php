<div>
    <!-- Upload Modal -->
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-data x-show="$wire.showUploadModal" x-transition>
            <div class="bg-card border border-border rounded-[var(--radius-large)] shadow-elevated w-full max-w-2xl" @click.away="$wire.closeUploadModal()">
                <!-- Modal Header -->
                <div class="border-b border-border p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-semibold text-card-foreground">Upload Bank Statement</h3>
                            <p class="text-sm text-muted-foreground mt-1">Upload a PDF or image of your bank statement for AI processing</p>
                        </div>
                        <button wire:click="closeUploadModal" class="text-muted-foreground hover:text-card-foreground">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    @if($errorMessage)
                        <div class="mb-4 p-4 bg-destructive/10 border border-destructive rounded-[var(--radius-md)] text-destructive text-sm">
                            {{ $errorMessage }}
                        </div>
                    @endif

                    <!-- File Upload Dropzone -->
                    <div class="space-y-4">
                        <div 
                            x-data="{ 
                                isDragging: false,
                                handleDrop(e) {
                                    this.isDragging = false;
                                    if (e.dataTransfer.files.length) {
                                        @this.upload('statementFile', e.dataTransfer.files[0]);
                                    }
                                }
                            }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            :class="{ 'border-primary bg-primary/5': isDragging }"
                            class="border-2 border-dashed border-border rounded-[var(--radius-lg)] p-12 text-center hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer"
                            onclick="document.getElementById('statementFileInput').click()">
                            
                            @if($statementFile)
                                <svg class="mx-auto h-16 w-16 text-chart-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-4 text-lg font-medium text-card-foreground">{{ $statementFile->getClientOriginalName() }}</p>
                                <p class="text-sm text-muted-foreground">{{ number_format($statementFile->getSize() / 1024, 2) }} KB</p>
                            @else
                                <svg class="mx-auto h-16 w-16 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-4 text-lg font-medium text-card-foreground">Drop your statement here</p>
                                <p class="text-sm text-muted-foreground">or click to browse</p>
                                <p class="text-xs text-muted-foreground mt-2">PDF, JPG, PNG, GIF, or WebP (max 10MB)</p>
                            @endif
                            
                            <input 
                                id="statementFileInput"
                                type="file" 
                                wire:model="statementFile" 
                                accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                                class="hidden">
                        </div>

                        @error('statementFile')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror

                        <!-- Upload Progress -->
                        <div wire:loading wire:target="statementFile" class="text-center">
                            <div class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Uploading file...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="border-t border-border p-6">
                    <div class="flex items-center justify-end gap-3">
                        <button 
                            wire:click="closeUploadModal" 
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors">
                            Cancel
                        </button>
                        <button 
                            wire:click="processStatement" 
                            :disabled="$wire.isProcessing || !$wire.statementFile"
                            class="px-6 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                            <span wire:loading.remove wire:target="processStatement">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </span>
                            <svg wire:loading wire:target="processStatement" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="processStatement">Process with AI</span>
                            <span wire:loading wire:target="processStatement">Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Preview Modal -->
    @if($showPreviewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-data x-show="$wire.showPreviewModal" x-transition>
            <div class="bg-card border border-border rounded-[var(--radius-large)] shadow-elevated w-full max-w-6xl max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-card border-b border-border p-6 z-10">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-semibold text-card-foreground">Review & Remove Errors</h3>
                            <p class="text-sm text-muted-foreground mt-1">Review AI-extracted data and remove any incorrect transactions before saving</p>
                        </div>
                        <button wire:click="closePreviewModal" class="text-muted-foreground hover:text-card-foreground">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6">
                    <!-- Account Information -->
                    <div>
                        <h4 class="text-lg font-semibold text-card-foreground mb-4">Account Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Institution Name *</label>
                                <input type="text" wire:model="institutionName" 
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                @error('institutionName') <span class="text-xs text-destructive">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Account Name *</label>
                                <input type="text" wire:model="accountName" 
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                @error('accountName') <span class="text-xs text-destructive">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Last 4 Digits</label>
                                <input type="text" wire:model="accountNumberLast4" maxlength="4"
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Account Type *</label>
                                <select wire:model="accountType" 
                                        class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option value="checking">Checking</option>
                                    <option value="savings">Savings</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="investment">Investment</option>
                                    <option value="loan">Loan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Current/Ending Balance *</label>
                                <input type="number" step="0.01" wire:model="endingBalance" 
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                <p class="text-xs text-muted-foreground mt-1">For statements: ending balance. For screenshots: current balance.</p>
                                @error('endingBalance') <span class="text-xs text-destructive">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Available Balance <span class="text-xs text-muted-foreground">(optional)</span></label>
                                <input type="number" step="0.01" wire:model="availableBalance" placeholder="Clear if N/A"
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                       onfocus="if(this.value == '0') this.value = ''">
                                <p class="text-xs text-muted-foreground mt-1">For credit cards only. Clear the 0 if not applicable.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Credit Limit <span class="text-xs text-muted-foreground">(optional)</span></label>
                                <input type="number" step="0.01" wire:model="creditLimit" placeholder="Clear if N/A"
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                       onfocus="if(this.value == '0') this.value = ''">
                                <p class="text-xs text-muted-foreground mt-1">Only for credit accounts. Clear the 0 if not applicable.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Currency</label>
                                <input type="text" wire:model="currency" maxlength="3"
                                       class="w-full px-4 py-2 bg-background border border-input rounded-[var(--radius-sm)] text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>
                        </div>
                    </div>

                    <!-- Transactions -->
                    @if($parsedTransactions && count($parsedTransactions) > 0)
                        <div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-4">
                                Transactions ({{ count($parsedTransactions) }})
                                <span class="text-sm font-normal text-muted-foreground ml-2">Click × to remove errors</span>
                            </h4>
                            <div class="bg-muted/30 rounded-[var(--radius-md)] overflow-hidden">
                                <div class="max-h-96 overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-muted sticky top-0">
                                            <tr>
                                                <th class="text-left p-3 text-xs font-medium text-muted-foreground uppercase">Date</th>
                                                <th class="text-left p-3 text-xs font-medium text-muted-foreground uppercase">Description</th>
                                                <th class="text-right p-3 text-xs font-medium text-muted-foreground uppercase">Amount</th>
                                                <th class="text-center p-3 text-xs font-medium text-muted-foreground uppercase">Type</th>
                                                <th class="text-center p-3 text-xs font-medium text-muted-foreground uppercase">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border">
                                            @foreach($parsedTransactions as $index => $txn)
                                                <tr class="hover:bg-muted/50 group">
                                                    <td class="p-3 text-card-foreground">{{ \Carbon\Carbon::parse($txn['date'])->format('M d, Y') }}</td>
                                                    <td class="p-3 text-card-foreground">{{ $txn['description'] }}</td>
                                                    <td class="p-3 text-right font-semibold {{ $txn['amount'] > 0 ? 'text-destructive' : 'text-chart-2' }}">
                                                        ${{ number_format(abs($txn['amount']), 2) }}
                                                    </td>
                                                    <td class="p-3 text-center">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn['type'] === 'debit' ? 'bg-destructive/20 text-destructive' : 'bg-chart-2/20 text-chart-2' }}">
                                                            {{ ucfirst($txn['type'] ?? 'debit') }}
                                                        </span>
                                                    </td>
                                                    <td class="p-3 text-center">
                                                        <button 
                                                            wire:click="removeTransaction({{ $index }})"
                                                            class="text-muted-foreground hover:text-destructive transition-colors opacity-0 group-hover:opacity-100"
                                                            title="Remove this transaction">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8 text-muted-foreground">
                            <p>No transactions were extracted from the statement.</p>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="sticky bottom-0 bg-card border-t border-border p-6">
                    <div class="flex items-center justify-end gap-3">
                        <button 
                            wire:click="closePreviewModal" 
                            type="button"
                            class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-card-foreground transition-colors">
                            Cancel
                        </button>
                        <button 
                            wire:click="saveManualAccount" 
                            class="px-6 py-2 bg-primary text-primary-foreground rounded-[var(--radius-md)] font-semibold text-sm hover:opacity-90 transition-all duration-150">
                            Save Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
