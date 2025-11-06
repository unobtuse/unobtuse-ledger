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
 * Manages user pay schedules with frequency configuration
 * and next pay date calculations.
 */
class PayScheduleManager extends Component
{
    // Form fields
    public string $frequency = 'monthly'; // 'weekly', 'biweekly', 'semi-monthly', 'monthly'
    public float $amount = 0;
    public string $pay_day = '1'; // Day of week (0-6) for weekly, day of month (1-31) for monthly
    public string $second_pay_day = '15'; // For semi-monthly
    public string $start_date = ''; // For biweekly
    public bool $is_gross = false;
    
    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public ?int $selectedScheduleId = null;
    
    /**
     * Mount component
     */
    public function mount(): void
    {
        $this->start_date = now()->toDateString();
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
    public function edit(int $scheduleId): void
    {
        $schedule = PaySchedule::where('id', $scheduleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $this->selectedScheduleId = $scheduleId;
        $this->frequency = $schedule->frequency;
        $this->amount = $schedule->amount;
        $this->pay_day = $schedule->pay_day ?? '1';
        $this->second_pay_day = $schedule->second_pay_day ?? '15';
        $this->start_date = $schedule->start_date ?? now()->toDateString();
        $this->is_gross = $schedule->is_gross;
        
        $this->showEditModal = true;
    }
    
    /**
     * Save pay schedule
     */
    public function save(): void
    {
        $this->validate([
            'frequency' => 'required|in:weekly,biweekly,semi-monthly,monthly',
            'amount' => 'required|numeric|min:0',
            'pay_day' => 'required',
            'second_pay_day' => 'required_if:frequency,semi-monthly',
            'start_date' => 'required_if:frequency,biweekly|nullable|date',
        ]);
        
        $data = [
            'user_id' => auth()->id(),
            'frequency' => $this->frequency,
            'amount' => $this->amount,
            'pay_day' => $this->frequency === 'weekly' || $this->frequency === 'biweekly' || $this->frequency === 'monthly' || $this->frequency === 'semi-monthly' ? $this->pay_day : null,
            'second_pay_day' => $this->frequency === 'semi-monthly' ? $this->second_pay_day : null,
            'start_date' => $this->frequency === 'biweekly' ? $this->start_date : null,
            'is_gross' => $this->is_gross,
            'is_active' => true,
        ];
        
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
            PaySchedule::create($data);
            session()->flash('success', 'Pay schedule created successfully!');
        }
        
        $this->closeModal();
        $this->resetForm();
    }
    
    /**
     * Activate a pay schedule
     */
    public function activate(int $scheduleId): void
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
    public function delete(int $scheduleId): void
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
        $this->frequency = 'monthly';
        $this->amount = 0;
        $this->pay_day = '1';
        $this->second_pay_day = '15';
        $this->start_date = now()->toDateString();
        $this->is_gross = false;
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
     * Calculate next 6 pay dates
     */
    protected function getUpcomingPayDates(): array
    {
        $schedule = $this->getActiveSchedule();
        
        if (!$schedule) {
            return [];
        }
        
        $dates = [];
        $currentDate = now();
        
        for ($i = 0; $i < 6; $i++) {
            $nextDate = $this->calculateNextPayDate($schedule, $currentDate);
            if ($nextDate) {
                $dates[] = $nextDate->format('Y-m-d');
                $currentDate = $nextDate->addDay();
            }
        }
        
        return $dates;
    }
    
    /**
     * Calculate next pay date based on schedule
     */
    protected function calculateNextPayDate(PaySchedule $schedule, Carbon $fromDate): ?Carbon
    {
        switch ($schedule->frequency) {
            case 'weekly':
                $dayOfWeek = (int) $schedule->pay_day;
                $nextDate = $fromDate->copy()->next($dayOfWeek);
                if ($fromDate->dayOfWeek === $dayOfWeek && $fromDate->isFuture()) {
                    return $fromDate;
                }
                return $nextDate;
                
            case 'biweekly':
                $startDate = Carbon::parse($schedule->start_date);
                $daysDiff = $fromDate->diffInDays($startDate);
                $weeksPassed = floor($daysDiff / 14);
                $nextDate = $startDate->copy()->addWeeks($weeksPassed + 1);
                while ($nextDate->isPast()) {
                    $nextDate->addWeeks(2);
                }
                return $nextDate;
                
            case 'semi-monthly':
                $day1 = (int) $schedule->pay_day;
                $day2 = (int) $schedule->second_pay_day;
                $currentDay = $fromDate->day;
                
                if ($currentDay < $day1) {
                    return $fromDate->copy()->day($day1);
                } elseif ($currentDay < $day2) {
                    return $fromDate->copy()->day($day2);
                } else {
                    return $fromDate->copy()->addMonth()->day($day1);
                }
                
            case 'monthly':
                $payDay = (int) $schedule->pay_day;
                if ($fromDate->day < $payDay) {
                    return $fromDate->copy()->day($payDay);
                } else {
                    return $fromDate->copy()->addMonth()->day($payDay);
                }
                
            default:
                return null;
        }
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        return view('livewire.pay-schedules.pay-schedule-manager', [
            'paySchedules' => $this->getPaySchedules(),
            'activeSchedule' => $this->getActiveSchedule(),
            'upcomingPayDates' => $this->getUpcomingPayDates(),
        ]);
    }
}


