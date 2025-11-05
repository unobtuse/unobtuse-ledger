<?php

declare(strict_types=1);

namespace App\Models;

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
        $current = $this->next_pay_date ?? now();

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
        return now()->diffInDays($this->next_pay_date, false);
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
}
