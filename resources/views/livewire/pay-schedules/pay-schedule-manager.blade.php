<div class="space-y-6">
    <!-- Active Pay Schedule Overview -->
    @if($activeSchedule)
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold text-card-foreground mb-4">Active Pay Schedule</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm text-muted-foreground">Frequency</p>
                    <p class="text-lg font-medium text-card-foreground mt-1">{{ ucfirst($activeSchedule->frequency) }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Amount Per Pay Period</p>
                    <p class="text-lg font-medium text-card-foreground mt-1">${{ number_format($activeSchedule->amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Next Payday</p>
                    <p class="text-lg font-medium text-chart-2 mt-1">
                        {{ count($upcomingPayDates) > 0 ? \Carbon\Carbon::parse($upcomingPayDates[0])->format('M d, Y') : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Upcoming Pay Dates -->
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Upcoming Pay Dates</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($upcomingPayDates as $index => $date)
                    <div class="p-4 bg-muted rounded-lg border border-border">
                        <p class="text-sm text-muted-foreground">Pay #{{ $index + 1 }}</p>
                        <p class="text-base font-medium text-card-foreground mt-1">
                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                        </p>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ \Carbon\Carbon::parse($date)->diffForHumans() }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-card border border-border rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-card-foreground">No Pay Schedule Set</h3>
            <p class="mt-2 text-sm text-muted-foreground">Set up your pay schedule to track bills due before payday.</p>
            <button wire:click="create" 
                    class="mt-6 inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:opacity-90">
                Create Pay Schedule
            </button>
        </div>
    @endif

    <!-- All Pay Schedules -->
    @if($paySchedules->count() > 0)
        <div class="bg-card border border-border rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-card-foreground">All Pay Schedules</h3>
                <button wire:click="create" 
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:opacity-90">
                    Add New
                </button>
            </div>
            <div class="space-y-3">
                @foreach($paySchedules as $schedule)
                    <div class="flex items-center justify-between p-4 border border-border rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h4 class="font-medium text-card-foreground">{{ ucfirst($schedule->frequency) }}</h4>
                                @if($schedule->is_active)
                                    <span class="text-xs px-2 py-0.5 bg-chart-2/20 text-chart-2 rounded-full">Active</span>
                                @endif
                            </div>
                            <p class="text-sm text-muted-foreground mt-1">
                                ${{ number_format($schedule->amount, 2) }} per pay period
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(!$schedule->is_active)
                                <button wire:click="activate({{ $schedule->id }})" 
                                        class="px-3 py-1 text-sm border border-border rounded-lg text-muted-foreground hover:bg-muted">
                                    Activate
                                </button>
                            @endif
                            <button wire:click="edit({{ $schedule->id }})" 
                                    class="px-3 py-1 text-sm border border-border rounded-lg text-muted-foreground hover:bg-muted">
                                Edit
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Create/Edit Modal -->
    @if($showCreateModal || $showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="closeModal"></div>
                <div class="relative bg-card border border-border rounded-lg shadow-elevated max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-card-foreground mb-4">
                        {{ $showEditModal ? 'Edit Pay Schedule' : 'Create Pay Schedule' }}
                    </h3>
                    <form wire:submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Frequency *</label>
                            <select wire:model.live="frequency" required
                                    class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground">
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Bi-weekly</option>
                                <option value="semi-monthly">Semi-monthly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Amount Per Pay Period *</label>
                            <input type="number" step="0.01" wire:model="amount" required
                                   class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground">
                        </div>
                        @if($frequency === 'weekly')
                            <div>
                                <label class="block text-sm font-medium text-muted-foreground mb-1">Pay Day *</label>
                                <select wire:model="pay_day" required
                                        class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground">
                                    <option value="0">Sunday</option>
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                </select>
                            </div>
                        @elseif($frequency === 'biweekly')
                            <div>
                                <label class="block text-sm font-medium text-muted-foreground mb-1">Start Date *</label>
                                <input type="date" wire:model="start_date" required
                                       class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground">
                            </div>
                        @elseif($frequency === 'semi-monthly')
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-muted-foreground mb-1">First Pay Day *</label>
                                    <input type="number" min="1" max="31" wire:model="pay_day" required
                                           class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-muted-foreground mb-1">Second Pay Day *</label>
                                    <input type="number" min="1" max="31" wire:model="second_pay_day" required
                                           class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground">
                                </div>
                            </div>
                        @elseif($frequency === 'monthly')
                            <div>
                                <label class="block text-sm font-medium text-muted-foreground mb-1">Pay Day (1-31) *</label>
                                <input type="number" min="1" max="31" wire:model="pay_day" required
                                       class="w-full px-3 py-2 bg-background border border-input rounded-lg text-foreground">
                            </div>
                        @endif
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="is_gross" id="is_gross"
                                   class="rounded border-input text-primary focus:ring-ring">
                            <label for="is_gross" class="text-sm text-muted-foreground">This is gross income (before taxes)</label>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg font-medium hover:opacity-90">
                                {{ $showEditModal ? 'Update' : 'Create' }}
                            </button>
                            <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 border border-border rounded-lg text-muted-foreground hover:bg-muted">
                                Cancel
                            </button>
                            @if($showEditModal)
                                <button type="button" wire:click="delete({{ $selectedScheduleId }})"
                                        class="px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:opacity-90">
                                    Delete
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>


