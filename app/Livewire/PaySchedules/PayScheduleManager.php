<?php

declare(strict_types=1);

namespace App\Livewire\PaySchedules;

use App\Models\PaySchedule;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Pay Schedule Manager Livewire Component
 * 
 * Manages user pay schedules with frequency configuration,
 * bills integration, and income projections.
 */
class PayScheduleManager extends Component
{
    // View mode: 'combined' or 'individual'
    public string $viewMode = 'combined';
    
    // Form fields - matching model structure
    public string $frequency = 'biweekly';
    public ?float $gross_pay = null;
    public ?float $net_pay = null;
    public string $currency = 'USD';
    public ?string $employer_name = null;
    public ?string $pay_day_of_week = null; // For weekly/biweekly: monday, tuesday, etc.
    public ?int $pay_day_of_month_1 = null; // For monthly/semimonthly: 1-31
    public ?int $pay_day_of_month_2 = null; // For semimonthly: 1-31
    public ?string $next_pay_date = null;
    public ?string $notes = null;
    public bool $is_active = true; // Whether to set schedule as active
    
    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public ?string $selectedScheduleId = null;
    
    /**
     * Mount component
     */
    public function mount(): void
    {
        $this->next_pay_date = now()->addWeeks(2)->toDateString();
        
        // Load view mode preference or default to combined
        $this->viewMode = auth()->user()->getPreference('pay_schedule_view_mode', 'combined');
    }
    
    /**
     * Toggle view mode
     */
    public function toggleViewMode(string $mode): void
    {
        if (in_array($mode, ['combined', 'individual'])) {
            $this->viewMode = $mode;
            auth()->user()->setPreference('pay_schedule_view_mode', $mode);
        }
    }
    
    /**
     * Open create modal
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }
    
    /**
     * Open edit modal
     */
    public function edit(string $scheduleId): void
    {
        $schedule = PaySchedule::where('id', $scheduleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $this->selectedScheduleId = $scheduleId;
        $this->frequency = $schedule->frequency;
        $this->gross_pay = $schedule->gross_pay;
        $this->net_pay = $schedule->net_pay;
        $this->currency = $schedule->currency ?? 'USD';
        $this->employer_name = $schedule->employer_name;
        $this->pay_day_of_week = $schedule->pay_day_of_week;
        $this->pay_day_of_month_1 = $schedule->pay_day_of_month_1;
        $this->pay_day_of_month_2 = $schedule->pay_day_of_month_2;
        $this->next_pay_date = $schedule->next_pay_date?->toDateString() ?? now()->addWeeks(2)->toDateString();
        $this->notes = $schedule->notes;
        $this->is_active = $schedule->is_active;
        
        $this->showEditModal = true;
    }
    
    /**
     * Save pay schedule
     */
    public function save(): void
    {
        $rules = [
            'frequency' => 'required|in:weekly,biweekly,semimonthly,monthly,custom',
            'net_pay' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'next_pay_date' => 'required|date|after_or_equal:today',
        ];

        // Add conditional rules based on frequency
        if ($this->frequency === 'weekly' || $this->frequency === 'biweekly') {
            $rules['pay_day_of_week'] = 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday';
        } elseif ($this->frequency === 'semimonthly') {
            $rules['pay_day_of_month_1'] = 'required|integer|min:1|max:31';
            $rules['pay_day_of_month_2'] = 'required|integer|min:1|max:31';
        } elseif ($this->frequency === 'monthly') {
            $rules['pay_day_of_month_1'] = 'required|integer|min:1|max:31';
        }

        $this->validate($rules);
        
        $data = [
            'user_id' => auth()->id(),
            'frequency' => $this->frequency,
            'net_pay' => $this->net_pay,
            'gross_pay' => $this->gross_pay,
            'currency' => $this->currency,
            'employer_name' => $this->employer_name,
            'next_pay_date' => $this->next_pay_date,
            'notes' => $this->notes,
        ];

        // Set frequency-specific fields
        if ($this->frequency === 'weekly' || $this->frequency === 'biweekly') {
            $data['pay_day_of_week'] = $this->pay_day_of_week;
            $data['pay_day_of_month_1'] = null;
            $data['pay_day_of_month_2'] = null;
        } elseif ($this->frequency === 'semimonthly') {
            $data['pay_day_of_month_1'] = $this->pay_day_of_month_1;
            $data['pay_day_of_month_2'] = $this->pay_day_of_month_2;
            $data['pay_day_of_week'] = null;
        } elseif ($this->frequency === 'monthly') {
            $data['pay_day_of_month_1'] = $this->pay_day_of_month_1;
            $data['pay_day_of_month_2'] = null;
            $data['pay_day_of_week'] = null;
        }
        
        if ($this->showEditModal && $this->selectedScheduleId) {
            // Update existing schedule
            $data['is_active'] = $this->is_active;
            PaySchedule::where('id', $this->selectedScheduleId)
                ->where('user_id', auth()->id())
                ->update($data);
            
            session()->flash('success', 'Pay schedule updated successfully!');
        } else {
            // Create new schedule - set is_active based on form or default to true for first schedule
            $data['is_active'] = $this->is_active ?? (PaySchedule::where('user_id', auth()->id())->count() === 0);
            PaySchedule::create($data);
            session()->flash('success', 'Pay schedule created successfully!');
        }
        
        $this->closeModal();
        $this->resetForm();
    }
    
    /**
     * Toggle active status of a pay schedule
     */
    public function toggleActive(string $scheduleId): void
    {
        $schedule = PaySchedule::where('id', $scheduleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $schedule->update(['is_active' => !$schedule->is_active]);
        
        $status = $schedule->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Pay schedule {$status}!");
    }
    
    /**
     * Delete pay schedule
     */
    public function delete(string $scheduleId): void
    {
        PaySchedule::where('id', $scheduleId)
            ->where('user_id', auth()->id())
            ->delete();
        
        session()->flash('success', 'Pay schedule deleted successfully!');
        $this->closeModal();
    }
    
    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->selectedScheduleId = null;
    }
    
    /**
     * Reset form
     */
    protected function resetForm(): void
    {
        $this->frequency = 'biweekly';
        $this->gross_pay = null;
        $this->net_pay = null;
        $this->currency = 'USD';
        $this->employer_name = null;
        $this->pay_day_of_week = null;
        $this->pay_day_of_month_1 = null;
        $this->pay_day_of_month_2 = null;
        $this->next_pay_date = now()->addWeeks(2)->toDateString();
        $this->notes = null;
        $this->is_active = true;
        $this->selectedScheduleId = null;
    }
    
    /**
     * Get all pay schedules
     */
    protected function getPaySchedules()
    {
        return PaySchedule::where('user_id', auth()->id())
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Get active pay schedules (multiple)
     */
    protected function getActiveSchedules()
    {
        return PaySchedule::where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('next_pay_date', 'asc')
            ->get();
    }
    
    /**
     * Get active pay schedule (single, for backward compatibility)
     */
    protected function getActiveSchedule(): ?PaySchedule
    {
        return $this->getActiveSchedules()->first();
    }
    
    /**
     * Get combined metrics from all active schedules
     */
    protected function getCombinedMetrics(): array
    {
        $schedules = $this->getActiveSchedules();
        
        if ($schedules->isEmpty()) {
            return [
                'total_net_pay' => 0,
                'earliest_pay_date' => null,
                'days_until' => null,
                'bills_due' => collect([]),
                'total_bills_due' => 0,
                'upcoming_pay_dates' => [],
                'income_projection' => [],
                'currency' => 'USD',
                'active_count' => 0,
            ];
        }
        
        $earliestPayDate = PaySchedule::getEarliestPayDate($schedules);
        $combinedNetPay = PaySchedule::getCombinedNetPay($schedules);
        $upcomingDates = PaySchedule::getCombinedUpcomingPayDates($schedules, 6);
        $incomeProjection = PaySchedule::getCombinedIncomeProjection($schedules, 6);
        
        // Get bills due before earliest payday
        $billsDue = collect([]);
        if ($earliestPayDate) {
            $billsDue = \App\Models\Bill::where('user_id', auth()->id())
                ->dueBeforePayday($earliestPayDate)
                ->orderBy('next_due_date', 'asc')
                ->get();
        }
        
        // Get primary currency (from first schedule)
        $currency = $schedules->first()->currency ?? 'USD';
        
        return [
            'total_net_pay' => $combinedNetPay,
            'earliest_pay_date' => $earliestPayDate,
            'days_until' => $earliestPayDate ? now()->diffInDays($earliestPayDate, false) : null,
            'bills_due' => $billsDue,
            'total_bills_due' => $billsDue->sum('amount'),
            'upcoming_pay_dates' => array_map(fn($date) => $date->toDateString(), $upcomingDates),
            'income_projection' => $incomeProjection,
            'currency' => $currency,
            'active_count' => $schedules->count(),
        ];
    }
    
    /**
     * Get individual metrics for each active schedule
     */
    protected function getIndividualMetrics(): array
    {
        $schedules = $this->getActiveSchedules();
        $metrics = [];
        
        foreach ($schedules as $schedule) {
            $upcomingDates = $schedule->calculateUpcomingPayDates(6);
            $billsDue = $schedule->getBillsDueBeforePayday();
            
            // Calculate income projection for this schedule
            $incomeProjection = [];
            if ($schedule->net_pay) {
                $dates = $schedule->calculateUpcomingPayDates(12);
                $months = [];
                foreach ($dates as $date) {
                    $monthKey = $date->format('Y-m');
                    if (!isset($months[$monthKey])) {
                        $months[$monthKey] = [
                            'month' => $date->format('F Y'),
                            'pay_count' => 0,
                            'total' => 0,
                        ];
                    }
                    $months[$monthKey]['pay_count']++;
                    $months[$monthKey]['total'] += (float) $schedule->net_pay;
                }
                $incomeProjection = array_slice(array_values($months), 0, 6);
            }
            
            $metrics[] = [
                'schedule' => $schedule,
                'upcoming_pay_dates' => array_map(fn($date) => $date->toDateString(), $upcomingDates),
                'bills_due' => $billsDue,
                'total_bills_due' => $billsDue->sum('amount'),
                'income_projection' => $incomeProjection,
            ];
        }
        
        return $metrics;
    }
    
    /**
     * Get upcoming pay dates using model method (for backward compatibility)
     */
    protected function getUpcomingPayDates(): array
    {
        if ($this->viewMode === 'combined') {
            $combined = $this->getCombinedMetrics();
            return $combined['upcoming_pay_dates'];
        }
        
        $schedule = $this->getActiveSchedule();
        if (!$schedule) {
            return [];
        }
        
        $dates = $schedule->calculateUpcomingPayDates(6);
        return array_map(fn($date) => $date->toDateString(), $dates);
    }
    
    /**
     * Get bills due before next payday (for backward compatibility)
     */
    protected function getBillsDueBeforePayday()
    {
        if ($this->viewMode === 'combined') {
            $combined = $this->getCombinedMetrics();
            return $combined['bills_due'];
        }
        
        $schedule = $this->getActiveSchedule();
        if (!$schedule || !$schedule->next_pay_date) {
            return collect([]);
        }
        
        return $schedule->getBillsDueBeforePayday();
    }
    
    /**
     * Get income projection for next 6 months (for backward compatibility)
     */
    protected function getIncomeProjection(): array
    {
        if ($this->viewMode === 'combined') {
            $combined = $this->getCombinedMetrics();
            return $combined['income_projection'];
        }
        
        $schedule = $this->getActiveSchedule();
        if (!$schedule || !$schedule->net_pay) {
            return [];
        }
        
        $projection = [];
        $dates = $schedule->calculateUpcomingPayDates(12);
        $months = [];
        
        foreach ($dates as $date) {
            $monthKey = $date->format('Y-m');
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'month' => $date->format('F Y'),
                    'pay_count' => 0,
                    'total' => 0,
                ];
            }
            $months[$monthKey]['pay_count']++;
            $months[$monthKey]['total'] += (float) $schedule->net_pay;
        }
        
        return array_slice($months, 0, 6);
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        $activeSchedules = $this->getActiveSchedules();
        $combinedMetrics = $this->getCombinedMetrics();
        $individualMetrics = $this->getIndividualMetrics();
        
        return view('livewire.pay-schedules.pay-schedule-manager', [
            'paySchedules' => $this->getPaySchedules(),
            'activeSchedules' => $activeSchedules,
            'activeSchedule' => $activeSchedules->first(), // For backward compatibility
            'combinedMetrics' => $combinedMetrics,
            'individualMetrics' => $individualMetrics,
            'viewMode' => $this->viewMode,
        ]);
    }
}
