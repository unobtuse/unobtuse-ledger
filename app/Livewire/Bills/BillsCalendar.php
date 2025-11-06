<?php

declare(strict_types=1);

namespace App\Livewire\Bills;

use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Bills Calendar Livewire Component
 * 
 * Displays bills on a visual calendar timeline with month navigation.
 */
class BillsCalendar extends Component
{
    public string $currentMonth;
    public string $currentYear;
    public ?string $selectedDate = null;
    public ?string $selectedBillId = null;
    
    public function mount(): void
    {
        $this->currentMonth = now()->format('m');
        $this->currentYear = now()->format('Y');
    }
    
    /**
     * Navigate to previous month
     */
    public function previousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->format('m');
        $this->currentYear = $date->format('Y');
    }
    
    /**
     * Navigate to next month
     */
    public function nextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->format('m');
        $this->currentYear = $date->format('Y');
    }
    
    /**
     * Go to current month
     */
    public function goToCurrentMonth(): void
    {
        $this->currentMonth = now()->format('m');
        $this->currentYear = now()->format('Y');
    }
    
    /**
     * Select a date
     */
    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }
    
    /**
     * Get calendar days for current month
     */
    protected function getCalendarDays(): array
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        
        // Start from the first day of the week (Sunday = 0)
        $startDate = $startOfMonth->copy()->startOfWeek();
        $endDate = $endOfMonth->copy()->endOfWeek();
        
        $days = [];
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $days[] = [
                'date' => $current->format('Y-m-d'),
                'day' => $current->day,
                'isCurrentMonth' => $current->month == $this->currentMonth,
                'isToday' => $current->isToday(),
                'isPast' => $current->isPast() && !$current->isToday(),
            ];
            $current->addDay();
        }
        
        return $days;
    }
    
    /**
     * Get bills for a specific date
     */
    protected function getBillsForDate(string $date): \Illuminate\Database\Eloquent\Collection
    {
        return Bill::where('user_id', auth()->id())
            ->whereDate('next_due_date', $date)
            ->orderBy('amount', 'asc')
            ->get();
    }
    
    /**
     * Get all bills for the current month
     */
    protected function getMonthBills(): \Illuminate\Database\Eloquent\Collection
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        
        return Bill::where('user_id', auth()->id())
            ->whereBetween('next_due_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->orderBy('next_due_date')
            ->get();
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        $calendarDays = $this->getCalendarDays();
        $monthBills = $this->getMonthBills();
        
        // Group bills by date for easy lookup
        $billsByDate = [];
        foreach ($monthBills as $bill) {
            $date = $bill->next_due_date->format('Y-m-d');
            if (!isset($billsByDate[$date])) {
                $billsByDate[$date] = [];
            }
            $billsByDate[$date][] = $bill;
        }
        
        return view('livewire.bills.bills-calendar', [
            'calendarDays' => $calendarDays,
            'billsByDate' => $billsByDate,
            'monthName' => Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F Y'),
            'selectedDateBills' => $this->selectedDate ? $this->getBillsForDate($this->selectedDate) : collect(),
        ]);
    }
}

