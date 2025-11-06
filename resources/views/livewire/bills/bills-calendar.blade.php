<div class="space-y-6">
    <!-- Calendar Header -->
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-card-foreground">{{ $monthName }}</h2>
                <p class="text-sm text-muted-foreground mt-1">View your bills on a calendar timeline</p>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="previousMonth" 
                        class="p-2 text-muted-foreground hover:text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button wire:click="goToCurrentMonth" 
                        class="px-3 py-2 text-sm font-medium text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                    Today
                </button>
                <button wire:click="nextMonth" 
                        class="p-2 text-muted-foreground hover:text-card-foreground hover:bg-muted rounded-[var(--radius-sm)] transition-all duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-2">
            <!-- Day Headers -->
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                <div class="text-center text-sm font-medium text-muted-foreground py-2">
                    {{ $day }}
                </div>
            @endforeach

            <!-- Calendar Days -->
            @foreach($calendarDays as $day)
                <div class="min-h-[100px] border border-border rounded-[var(--radius-sm)] p-2 {{ $day['isCurrentMonth'] ? 'bg-card' : 'bg-muted/30' }} {{ $day['isToday'] ? 'ring-2 ring-primary' : '' }} transition-all duration-150 hover:shadow-sm"
                     wire:click="selectDate('{{ $day['date'] }}')"
                     x-data="{ showTooltip: false }"
                     @mouseenter="showTooltip = true"
                     @mouseleave="showTooltip = false">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium {{ $day['isCurrentMonth'] ? 'text-card-foreground' : 'text-muted-foreground' }} {{ $day['isToday'] ? 'text-primary' : '' }}">
                            {{ $day['day'] }}
                        </span>
                        @if(isset($billsByDate[$day['date']]))
                            <span class="text-xs font-medium text-chart-4 bg-chart-4/20 px-1.5 py-0.5 rounded-full">
                                {{ count($billsByDate[$day['date']]) }}
                            </span>
                        @endif
                    </div>
                    
                    <!-- Bills for this day -->
                    <div class="space-y-1 mt-1">
                        @if(isset($billsByDate[$day['date']]))
                            @foreach(array_slice($billsByDate[$day['date']], 0, 3) as $bill)
                                @php
                                    $statusColors = [
                                        'paid' => 'bg-chart-2/20 text-chart-2 border-chart-2/30',
                                        'upcoming' => 'bg-chart-4/20 text-chart-4 border-chart-4/30',
                                        'due' => 'bg-chart-5/20 text-chart-5 border-chart-5/30',
                                        'overdue' => 'bg-destructive/20 text-destructive border-destructive/30',
                                        'scheduled' => 'bg-chart-3/20 text-chart-3 border-chart-3/30',
                                    ];
                                    $statusColor = $statusColors[$bill->payment_status] ?? 'bg-muted text-muted-foreground border-border';
                                @endphp
                                <div class="text-xs p-1 rounded border {{ $statusColor }} truncate cursor-pointer hover:opacity-80 transition-opacity"
                                     wire:click.stop="$dispatch('show-bill-details', { billId: '{{ $bill->id }}' })"
                                     title="{{ $bill->name }} - ${{ number_format(abs((float) $bill->amount), 2) }}">
                                    {{ $bill->name }}
                                </div>
                            @endforeach
                            @if(count($billsByDate[$day['date']]) > 3)
                                <div class="text-xs text-muted-foreground text-center">
                                    +{{ count($billsByDate[$day['date']]) - 3 }} more
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Selected Date Bills -->
    @if($selectedDate && $selectedDateBills->count() > 0)
        <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">
                Bills Due on {{ \Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}
            </h3>
            <div class="space-y-3">
                @foreach($selectedDateBills as $bill)
                    <div class="flex items-center justify-between p-4 border border-border rounded-[var(--radius-sm)] hover:bg-muted/50 transition-all duration-150">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-semibold text-card-foreground">{{ $bill->name }}</h4>
                                @if($bill->is_autopay)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-chart-3/20 text-chart-3">
                                        Autopay
                                    </span>
                                @endif
                                @if($bill->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground">
                                        {{ ucfirst($bill->category) }}
                                    </span>
                                @endif
                            </div>
                            @if($bill->description)
                                <p class="text-sm text-muted-foreground">{{ $bill->description }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-4 ml-4">
                            <p class="text-lg font-semibold text-card-foreground">
                                ${{ number_format(abs((float) $bill->amount), 2) }}
                            </p>
                            <button wire:click="$dispatch('show-bill-details', { billId: '{{ $bill->id }}' })" 
                                    class="px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded-[var(--radius-sm)] hover:opacity-90 transition-all duration-150">
                                View Details
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Legend -->
    <div class="bg-card border border-border rounded-[var(--radius-default)] shadow-sm p-6">
        <h3 class="text-sm font-semibold text-card-foreground mb-3">Status Legend</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-chart-2/20 border border-chart-2/30"></div>
                <span class="text-xs text-muted-foreground">Paid</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-chart-4/20 border border-chart-4/30"></div>
                <span class="text-xs text-muted-foreground">Upcoming</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-chart-5/20 border border-chart-5/30"></div>
                <span class="text-xs text-muted-foreground">Due</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-destructive/20 border border-destructive/30"></div>
                <span class="text-xs text-muted-foreground">Overdue</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-chart-3/20 border border-chart-3/30"></div>
                <span class="text-xs text-muted-foreground">Scheduled</span>
            </div>
        </div>
    </div>
</div>

