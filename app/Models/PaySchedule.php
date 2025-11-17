<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PaySchedule Model
 * 
 * Represents a user's paycheck schedule.
 * Used to calculate bills due before next payday.
 */
class PaySchedule extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'frequency',
        'pay_day_of_week',
        'pay_day_of_month_1',
        'pay_day_of_month_2',
        'custom_schedule',
        'next_pay_date',
        'gross_pay',
        'net_pay',
        'currency',
        'employer_name',
        'is_active',
        'notes',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_pay_date' => 'date',
            'gross_pay' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'is_active' => 'boolean',
            'custom_schedule' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the pay schedule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate the next pay date based on frequency.
     *
     * @return Carbon
     */
    public function calculateNextPayDate(): Carbon
    {
        $current = ($this->next_pay_date ?? now())->copy();

        return match($this->frequency) {
            'weekly' => $this->calculateWeeklyPayDate($current),
            'biweekly' => $this->calculateBiweeklyPayDate($current),
            'semimonthly' => $this->calculateSemimonthlyPayDate($current),
            'monthly' => $this->calculateMonthlyPayDate($current),
            'custom' => $this->calculateCustomPayDate($current),
            default => $current->addWeeks(2), // Default to biweekly
        };
    }

    /**
     * Calculate weekly pay date.
     *
     * @param Carbon $current
     * @return Carbon
     */
    protected function calculateWeeklyPayDate(Carbon $current): Carbon
    {
        return $current->addWeek();
    }

    /**
     * Calculate biweekly pay date.
     *
     * @param Carbon $current
     * @return Carbon
     */
    protected function calculateBiweeklyPayDate(Carbon $current): Carbon
    {
        return $current->addWeeks(2);
    }

    /**
     * Calculate semimonthly pay date (twice per month).
     *
     * @param Carbon $current
     * @return Carbon
     */
    protected function calculateSemimonthlyPayDate(Carbon $current): Carbon
    {
        $day1 = $this->pay_day_of_month_1 ?? 1;
        $day2 = $this->pay_day_of_month_2 ?? 15;

        $currentDay = $current->day;

        // If current pay was on first date, next is second date
        if ($currentDay == $day1) {
            return $current->setDay(min($day2, $current->daysInMonth));
        }

        // Otherwise, next month's first date
        return $current->addMonth()->setDay(min($day1, $current->daysInMonth));
    }

    /**
     * Calculate monthly pay date.
     *
     * @param Carbon $current
     * @return Carbon
     */
    protected function calculateMonthlyPayDate(Carbon $current): Carbon
    {
        $payDay = $this->pay_day_of_month_1 ?? 1;
        return $current->addMonth()->setDay(min($payDay, $current->daysInMonth));
    }

    /**
     * Calculate custom pay date from schedule.
     *
     * @param Carbon $current
     * @return Carbon
     */
    protected function calculateCustomPayDate(Carbon $current): Carbon
    {
        if (!$this->custom_schedule || empty($this->custom_schedule)) {
            return $current->addWeeks(2);
        }

        // Find next date in custom schedule
        foreach ($this->custom_schedule as $date) {
            $payDate = Carbon::parse($date);
            if ($payDate->isAfter($current)) {
                return $payDate;
            }
        }

        // If no future dates, return first date of next cycle
        return Carbon::parse($this->custom_schedule[0])->addYear();
    }

    /**
     * Get rent allocation (25% of net pay).
     *
     * @return float
     */
    public function getRentAllocationAttribute(): float
    {
        if (!$this->net_pay) {
            return 0;
        }

        return (float) $this->net_pay * 0.25;
    }

    /**
     * Get formatted net pay.
     *
     * @return string
     */
    public function getFormattedNetPayAttribute(): string
    {
        if (!$this->net_pay) {
            return 'Not set';
        }

        return $this->currency . ' ' . number_format((float) $this->net_pay, 2);
    }

    /**
     * Get days until next paycheck.
     *
     * @return int
     */
    public function getDaysUntilPaydayAttribute(): int
    {
        if (!$this->next_pay_date) {
            return 0;
        }
        return (int) now()->diffInDays($this->next_pay_date, false);
    }

    /**
     * Get bills due before next payday.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBillsDueBeforePayday()
    {
        if (!$this->next_pay_date) {
            return collect([]);
        }

        return Bill::where('user_id', $this->user_id)
            ->dueBeforePayday($this->next_pay_date)
            ->orderBy('next_due_date', 'asc')
            ->get();
    }

    /**
     * Get total amount of bills due before next payday.
     *
     * @return float
     */
    public function getTotalBillsDueBeforePayday(): float
    {
        $bills = $this->getBillsDueBeforePayday();
        return (float) $bills->sum('amount');
    }

    /**
     * Get available funds after bills are paid.
     *
     * @return float
     */
    public function getAvailableAfterBillsAttribute(): float
    {
        if (!$this->net_pay) {
            return 0;
        }

        $billsTotal = $this->getTotalBillsDueBeforePayday();
        return max(0, (float) $this->net_pay - $billsTotal);
    }

    /**
     * Calculate next N pay dates from a given date.
     *
     * @param int $count Number of pay dates to calculate
     * @param Carbon|null $fromDate Starting date (defaults to next_pay_date or now)
     * @return array Array of Carbon dates
     */
    public function calculateUpcomingPayDates(int $count = 6, ?Carbon $fromDate = null): array
    {
        if (!$fromDate) {
            $fromDate = $this->next_pay_date ?? now();
        }

        $dates = [];
        $currentDate = $fromDate->copy();

        for ($i = 0; $i < $count; $i++) {
            if ($i === 0 && $currentDate->isFuture()) {
                // Use the stored next_pay_date if it's in the future
                $dates[] = $currentDate->copy();
                $currentDate = $this->calculateNextPayDateFrom($currentDate);
            } else {
                $nextDate = $this->calculateNextPayDateFrom($currentDate);
                $dates[] = $nextDate;
                $currentDate = $nextDate->copy();
            }
        }

        return $dates;
    }

    /**
     * Calculate next pay date from a given date.
     *
     * @param Carbon $fromDate
     * @return Carbon
     */
    public function calculateNextPayDateFrom(Carbon $fromDate): Carbon
    {
        return match($this->frequency) {
            'weekly' => $this->calculateWeeklyPayDateFrom($fromDate),
            'biweekly' => $this->calculateBiweeklyPayDateFrom($fromDate),
            'semimonthly' => $this->calculateSemimonthlyPayDateFrom($fromDate),
            'monthly' => $this->calculateMonthlyPayDateFrom($fromDate),
            'custom' => $this->calculateCustomPayDateFrom($fromDate),
            default => $fromDate->copy()->addWeeks(2),
        };
    }

    /**
     * Calculate weekly pay date from a given date.
     *
     * @param Carbon $fromDate
     * @return Carbon
     */
    protected function calculateWeeklyPayDateFrom(Carbon $fromDate): Carbon
    {
        if (!$this->pay_day_of_week) {
            return $fromDate->copy()->addWeek();
        }

        $dayMap = [
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
        ];

        $targetDay = $dayMap[strtolower($this->pay_day_of_week)] ?? Carbon::FRIDAY;
        $nextDate = $fromDate->copy()->next($targetDay);

        // If today is the pay day and it hasn't passed, return today
        if ($fromDate->dayOfWeek === $targetDay && $fromDate->isFuture()) {
            return $fromDate->copy();
        }

        return $nextDate;
    }

    /**
     * Calculate biweekly pay date from a given date.
     *
     * @param Carbon $fromDate
     * @return Carbon
     */
    protected function calculateBiweeklyPayDateFrom(Carbon $fromDate): Carbon
    {
        // Use next_pay_date as the reference point
        $referenceDate = $this->next_pay_date ?? now();
        
        // Calculate how many biweekly periods have passed
        $daysDiff = $fromDate->diffInDays($referenceDate);
        $periodsPassed = floor($daysDiff / 14);
        
        $nextDate = $referenceDate->copy()->addWeeks($periodsPassed * 2);
        
        // If the calculated date is in the past, add 2 more weeks
        while ($nextDate->isBefore($fromDate) || $nextDate->isSameDay($fromDate)) {
            $nextDate->addWeeks(2);
        }
        
        return $nextDate;
    }

    /**
     * Calculate semimonthly pay date from a given date.
     *
     * @param Carbon $fromDate
     * @return Carbon
     */
    protected function calculateSemimonthlyPayDateFrom(Carbon $fromDate): Carbon
    {
        $day1 = $this->pay_day_of_month_1 ?? 1;
        $day2 = $this->pay_day_of_month_2 ?? 15;
        $currentDay = $fromDate->day;
        $currentMonth = $fromDate->copy();

        // Determine which pay date we're closest to
        if ($currentDay < $day1) {
            // Before first payday of month
            return $currentMonth->setDay(min($day1, $currentMonth->daysInMonth));
        } elseif ($currentDay < $day2) {
            // Between first and second payday
            return $currentMonth->setDay(min($day2, $currentMonth->daysInMonth));
        } else {
            // After second payday, next is first of next month
            return $currentMonth->addMonth()->setDay(min($day1, $currentMonth->daysInMonth));
        }
    }

    /**
     * Calculate monthly pay date from a given date.
     *
     * @param Carbon $fromDate
     * @return Carbon
     */
    protected function calculateMonthlyPayDateFrom(Carbon $fromDate): Carbon
    {
        $payDay = $this->pay_day_of_month_1 ?? 1;
        $currentMonth = $fromDate->copy();

        if ($fromDate->day < $payDay) {
            // Before payday this month
            return $currentMonth->setDay(min($payDay, $currentMonth->daysInMonth));
        } else {
            // After payday, next month
            return $currentMonth->addMonth()->setDay(min($payDay, $currentMonth->daysInMonth));
        }
    }

    /**
     * Calculate custom pay date from schedule.
     *
     * @param Carbon $fromDate
     * @return Carbon
     */
    protected function calculateCustomPayDateFrom(Carbon $fromDate): Carbon
    {
        if (!$this->custom_schedule || empty($this->custom_schedule)) {
            return $fromDate->copy()->addWeeks(2);
        }

        // Find next date in custom schedule
        foreach ($this->custom_schedule as $date) {
            $payDate = Carbon::parse($date);
            if ($payDate->isAfter($fromDate) || $payDate->isSameDay($fromDate)) {
                return $payDate;
            }
        }

        // If no future dates, return first date of next cycle
        return Carbon::parse($this->custom_schedule[0])->addYear();
    }

    /**
     * Get formatted gross pay.
     *
     * @return string
     */
    public function getFormattedGrossPayAttribute(): string
    {
        if (!$this->gross_pay) {
            return 'Not set';
        }

        return $this->currency . ' ' . number_format((float) $this->gross_pay, 2);
    }

    /**
     * Get formatted rent allocation.
     *
     * @return string
     */
    public function getFormattedRentAllocationAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->rent_allocation, 2);
    }

    /**
     * Get formatted available after bills.
     *
     * @return string
     */
    public function getFormattedAvailableAfterBillsAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->available_after_bills, 2);
    }

    /**
     * Scope to active pay schedules.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get combined net pay from multiple schedules.
     *
     * @param \Illuminate\Database\Eloquent\Collection $schedules
     * @return float
     */
    public static function getCombinedNetPay($schedules): float
    {
        return (float) $schedules->sum(function ($schedule) {
            return (float) ($schedule->net_pay ?? 0);
        });
    }

    /**
     * Get earliest pay date from multiple schedules.
     *
     * @param \Illuminate\Database\Eloquent\Collection $schedules
     * @return Carbon|null
     */
    public static function getEarliestPayDate($schedules): ?Carbon
    {
        $dates = $schedules->map(function ($schedule) {
            return $schedule->next_pay_date;
        })->filter()->sort();

        return $dates->first();
    }

    /**
     * Get combined upcoming pay dates from multiple schedules.
     *
     * @param \Illuminate\Database\Eloquent\Collection $schedules
     * @param int $count Number of pay dates to return
     * @return array Array of Carbon dates, sorted chronologically
     */
    public static function getCombinedUpcomingPayDates($schedules, int $count = 6): array
    {
        $allDates = collect();

        foreach ($schedules as $schedule) {
            $dates = $schedule->calculateUpcomingPayDates($count * 2); // Get more dates to ensure we have enough
            foreach ($dates as $date) {
                if ($date->isFuture() || $date->isToday()) {
                    $allDates->push($date);
                }
            }
        }

        // Sort by date and get unique dates, then take first $count
        return $allDates->unique(function ($date) {
            return $date->toDateString();
        })->sort()->take($count)->values()->toArray();
    }

    /**
     * Get combined income projection from multiple schedules.
     *
     * @param \Illuminate\Database\Eloquent\Collection $schedules
     * @param int $months Number of months to project
     * @return array Array of monthly projections
     */
    public static function getCombinedIncomeProjection($schedules, int $months = 6): array
    {
        $projection = [];
        $monthsData = [];

        foreach ($schedules as $schedule) {
            if (!$schedule->net_pay) {
                continue;
            }

            // Get pay dates for each schedule (enough to cover the months)
            $payDates = $schedule->calculateUpcomingPayDates($months * 3); // Rough estimate

            foreach ($payDates as $date) {
                $monthKey = $date->format('Y-m');
                if (!isset($monthsData[$monthKey])) {
                    $monthsData[$monthKey] = [
                        'month' => $date->format('F Y'),
                        'pay_count' => 0,
                        'total' => 0,
                    ];
                }
                $monthsData[$monthKey]['pay_count']++;
                $monthsData[$monthKey]['total'] += (float) $schedule->net_pay;
            }
        }

        // Sort by month key and return first $months
        ksort($monthsData);
        return array_slice(array_values($monthsData), 0, $months);
    }
}
