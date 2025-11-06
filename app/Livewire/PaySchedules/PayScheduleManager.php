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
            PaySchedule::where('id', $this->selectedScheduleId)
                ->where('user_id', auth()->id())
                ->update($data);
            
            session()->flash('success', 'Pay schedule updated successfully!');
        } else {
            // Deactivate all other schedules for this user
            PaySchedule::where('user_id', auth()->id())
                ->update(['is_active' => false]);
            
            // Create new schedule
            $data['is_active'] = true;
            PaySchedule::create($data);
            session()->flash('success', 'Pay schedule created successfully!');
        }
        
        $this->closeModal();
        $this->resetForm();
    }
    
    /**
     * Activate a pay schedule
     */
    public function activate(string $scheduleId): void
    {
        // Deactivate all schedules
        PaySchedule::where('user_id', auth()->id())
            ->update(['is_active' => false]);
        
        // Activate selected schedule
        PaySchedule::where('id', $scheduleId)
            ->where('user_id', auth()->id())
            ->update(['is_active' => true]);
        
        session()->flash('success', 'Pay schedule activated!');
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
     * Get active pay schedule
     */
    protected function getActiveSchedule(): ?PaySchedule
    {
        return PaySchedule::where('user_id', auth()->id())
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Get upcoming pay dates using model method
     */
    protected function getUpcomingPayDates(): array
    {
        $schedule = $this->getActiveSchedule();
        
        if (!$schedule) {
            return [];
        }
        
        $dates = $schedule->calculateUpcomingPayDates(6);
        return array_map(fn($date) => $date->toDateString(), $dates);
    }
    
    /**
     * Get bills due before next payday
     */
    protected function getBillsDueBeforePayday()
    {
        $schedule = $this->getActiveSchedule();
        
        if (!$schedule || !$schedule->next_pay_date) {
            return collect([]);
        }
        
        return $schedule->getBillsDueBeforePayday();
    }
    
    /**
     * Get income projection for next 6 months
     */
    protected function getIncomeProjection(): array
    {
        $schedule = $this->getActiveSchedule();
        
        if (!$schedule || !$schedule->net_pay) {
            return [];
        }
        
        $projection = [];
        $dates = $schedule->calculateUpcomingPayDates(12); // Get 12 pay dates (roughly 6 months)
        $currentMonth = now()->format('Y-m');
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
        
        // Return first 6 months
        return array_slice($months, 0, 6);
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        $activeSchedule = $this->getActiveSchedule();
        $billsDue = $this->getBillsDueBeforePayday();
        $incomeProjection = $this->getIncomeProjection();
        
        return view('livewire.pay-schedules.pay-schedule-manager', [
            'paySchedules' => $this->getPaySchedules(),
            'activeSchedule' => $activeSchedule,
            'upcomingPayDates' => $this->getUpcomingPayDates(),
            'billsDue' => $billsDue,
            'totalBillsDue' => $billsDue->sum('amount'),
            'incomeProjection' => $incomeProjection,
        ]);
    }
}
