<div>
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-card-foreground mb-4">Payment History</h2>
        
        <!-- Filters -->
        <div class="flex items-center gap-4 mb-4">
            <div class="flex items-center gap-2">
                <button wire:click="filterByStatus('all')" 
                        class="px-3 py-1 rounded-lg text-sm {{ $statusFilter === 'all' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    All
                </button>
                <button wire:click="filterByStatus('pending')" 
                        class="px-3 py-1 rounded-lg text-sm {{ $statusFilter === 'pending' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    Pending
                </button>
                <button wire:click="filterByStatus('completed')" 
                        class="px-3 py-1 rounded-lg text-sm {{ $statusFilter === 'completed' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    Completed
                </button>
                <button wire:click="filterByStatus('failed')" 
                        class="px-3 py-1 rounded-lg text-sm {{ $statusFilter === 'failed' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}">
                    Failed
                </button>
            </div>
        </div>
    </div>

    @if($payments->count() > 0)
        <div class="space-y-4">
            @foreach($payments as $payment)
                <div class="bg-card border border-border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="font-semibold text-card-foreground">{{ $payment->recipient_name }}</h4>
                                <span class="px-2 py-1 rounded text-xs font-medium
                                    {{ $payment->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $payment->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                    {{ $payment->status === 'failed' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $payment->status === 'processing' ? 'bg-blue-100 text-blue-700' : '' }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                To: ••••{{ $payment->recipient_account_number }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                From: {{ $payment->account->institution_name }} - {{ $payment->account->account_name }}
                            </p>
                            @if($payment->memo)
                                <p class="text-sm text-muted-foreground mt-1">Memo: {{ $payment->memo }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-semibold text-card-foreground">{{ $payment->formatted_amount }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ $payment->created_at->format('M d, Y') }}
                            </p>
                            @if($payment->scheduled_date)
                                <p class="text-xs text-muted-foreground">
                                    Scheduled: {{ $payment->scheduled_date->format('M d, Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-muted-foreground">No payments found</p>
        </div>
    @endif
</div>
