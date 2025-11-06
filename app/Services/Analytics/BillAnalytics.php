<?php

namespace App\Services\Analytics;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BillAnalytics
{
    protected User $user;
    protected int $cacheDuration = 900; // 15 minutes

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get bills by category
     */
    public function getBillsByCategory(): array
    {
        return Cache::remember(
            "bill.by_category.{$this->user->id}",
            $this->cacheDuration,
            function () {
                $bills = $this->user->bills()->get();

                $byCategory = $bills->groupBy('category')
                    ->map(function ($bills, $category) {
                        return [
                            'category' => ucfirst($category),
                            'amount' => $bills->sum('amount'),
                            'count' => $bills->count(),
                        ];
                    })
                    ->sortByDesc('amount')
                    ->values();

                return [
                    'labels' => $byCategory->pluck('category')->toArray(),
                    'data' => $byCategory->pluck('amount')->toArray(),
                    'counts' => $byCategory->pluck('count')->toArray(),
                ];
            }
        );
    }

    /**
     * Get payment reliability score
     */
    public function getPaymentReliability(): array
    {
        return Cache::remember(
            "bill.payment_reliability.{$this->user->id}",
            $this->cacheDuration,
            function () {
                // Look at last 6 months of bills
                $startDate = Carbon::now()->subMonths(6);

                $bills = $this->user->bills()
                    ->whereNotNull('last_payment_date')
                    ->get();

                $totalPayments = 0;
                $onTimePayments = 0;
                $latePayments = 0;

                foreach ($bills as $bill) {
                    // Get payment history through transactions
                    $payments = $bill->transactions()
                        ->where('transaction_date', '>=', $startDate)
                        ->get();

                    foreach ($payments as $payment) {
                        $totalPayments++;

                        // Estimate if payment was on time based on transaction date vs typical due date
                        // This is simplified - in production you'd track this more precisely
                        $dayOfMonth = $payment->transaction_date->day;
                        $billDueDay = $bill->due_date ? $bill->due_date->day : null;

                        // Consider on-time if paid within 5 days of due date
                        if ($billDueDay && abs($dayOfMonth - $billDueDay) <= 5) {
                            $onTimePayments++;
                        } else {
                            $latePayments++;
                        }
                    }
                }

                $reliabilityScore = $totalPayments > 0
                    ? round(($onTimePayments / $totalPayments) * 100, 1)
                    : 0;

                return [
                    'score' => $reliabilityScore,
                    'total_payments' => $totalPayments,
                    'on_time' => $onTimePayments,
                    'late' => $latePayments,
                    'status' => $this->getReliabilityStatus($reliabilityScore),
                ];
            }
        );
    }

    /**
     * Get reliability status based on score
     */
    protected function getReliabilityStatus(float $score): string
    {
        if ($score >= 95) {
            return 'excellent';
        } elseif ($score >= 85) {
            return 'good';
        } elseif ($score >= 70) {
            return 'fair';
        } else {
            return 'needs_improvement';
        }
    }

    /**
     * Get monthly bill total trend
     */
    public function getMonthlyBillTrend(int $months = 12): array
    {
        return Cache::remember(
            "bill.monthly_trend.{$this->user->id}.{$months}",
            $this->cacheDuration,
            function () use ($months) {
                $labels = [];
                $data = [];

                for ($i = $months - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subMonths($i);
                    $labels[] = $date->format('M Y');

                    // Sum up all bills that would have been due that month
                    $monthTotal = $this->user->bills()->get()->sum(function ($bill) use ($date) {
                        // Check if bill would be due this month based on frequency
                        // Simplified calculation - assumes all bills are active
                        $multiplier = match($bill->frequency) {
                            'weekly' => 4.33,
                            'biweekly' => 2.17,
                            'monthly' => 1,
                            'quarterly' => 0.33,
                            'annual' => 0.083,
                            default => 1,
                        };

                        return $bill->amount * $multiplier;
                    });

                    $data[] = round($monthTotal, 2);
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                ];
            }
        );
    }

    /**
     * Get average bill amount by category
     */
    public function getAverageBillByCategory(): array
    {
        return Cache::remember(
            "bill.average_by_category.{$this->user->id}",
            $this->cacheDuration,
            function () {
                $bills = $this->user->bills()->get();

                $byCategory = $bills->groupBy('category')
                    ->map(function ($bills, $category) {
                        return [
                            'category' => ucfirst($category),
                            'average' => round($bills->avg('amount'), 2),
                            'count' => $bills->count(),
                        ];
                    })
                    ->sortByDesc('average')
                    ->values();

                return [
                    'labels' => $byCategory->pluck('category')->toArray(),
                    'data' => $byCategory->pluck('average')->toArray(),
                    'counts' => $byCategory->pluck('count')->toArray(),
                ];
            }
        );
    }

    /**
     * Get bill due date timeline (for current month)
     */
    public function getBillDueDateTimeline(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $bills = $this->user->bills()
            ->whereBetween('next_due_date', [$startOfMonth, $endOfMonth])
            ->orderBy('next_due_date')
            ->get();

        // Get pay schedule dates for the month
        $paySchedule = $this->user->paySchedules()->where('is_active', true)->first();
        $payDates = $paySchedule
            ? $paySchedule->calculateUpcomingPayDates(4)->filter(function ($date) use ($startOfMonth, $endOfMonth) {
                return $date->between($startOfMonth, $endOfMonth);
            })
            : collect();

        return [
            'bills' => $bills->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'name' => $bill->name,
                    'amount' => $bill->amount,
                    'formatted_amount' => '$' . number_format($bill->amount, 2),
                    'due_date' => $bill->next_due_date,
                    'day_of_month' => $bill->next_due_date->day,
                    'formatted_date' => $bill->next_due_date->format('M j'),
                    'status' => $bill->payment_status,
                    'is_overdue' => $bill->isOverdue(),
                    'category' => $bill->category,
                    'priority' => $bill->priority,
                ];
            })->toArray(),
            'paydays' => $payDates->map(function ($date) {
                return [
                    'date' => $date,
                    'day_of_month' => $date->day,
                    'formatted_date' => $date->format('M j'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get autopay vs manual split
     */
    public function getAutopayBreakdown(): array
    {
        return Cache::remember(
            "bill.autopay_breakdown.{$this->user->id}",
            $this->cacheDuration,
            function () {
                $bills = $this->user->bills()->get();

                $autopay = $bills->where('is_autopay', true);
                $manual = $bills->where('is_autopay', false);

                return [
                    'autopay_count' => $autopay->count(),
                    'autopay_amount' => $autopay->sum('amount'),
                    'autopay_percentage' => $bills->count() > 0
                        ? round(($autopay->count() / $bills->count()) * 100, 1)
                        : 0,
                    'manual_count' => $manual->count(),
                    'manual_amount' => $manual->sum('amount'),
                    'manual_percentage' => $bills->count() > 0
                        ? round(($manual->count() / $bills->count()) * 100, 1)
                        : 0,
                    'labels' => ['Autopay', 'Manual'],
                    'data' => [$autopay->count(), $manual->count()],
                ];
            }
        );
    }

    /**
     * Get upcoming bills summary
     */
    public function getUpcomingBills(int $days = 30): array
    {
        $endDate = Carbon::now()->addDays($days);

        $bills = $this->user->bills()
            ->where('next_due_date', '<=', $endDate)
            ->where('payment_status', '!=', 'paid')
            ->orderBy('next_due_date')
            ->get();

        $totalAmount = $bills->sum('amount');
        $overdueBills = $bills->filter(fn($b) => $b->isOverdue());
        $dueSoonBills = $bills->filter(fn($b) => $b->isDueSoon() && !$b->isOverdue());

        return [
            'total_bills' => $bills->count(),
            'total_amount' => $totalAmount,
            'formatted_total' => '$' . number_format($totalAmount, 2),
            'overdue_count' => $overdueBills->count(),
            'overdue_amount' => $overdueBills->sum('amount'),
            'due_soon_count' => $dueSoonBills->count(),
            'due_soon_amount' => $dueSoonBills->sum('amount'),
            'bills' => $bills->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'name' => $bill->name,
                    'amount' => $bill->amount,
                    'due_date' => $bill->next_due_date,
                    'days_until_due' => Carbon::now()->diffInDays($bill->next_due_date, false),
                    'is_overdue' => $bill->isOverdue(),
                    'is_due_soon' => $bill->isDueSoon(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Clear cache for this user
     */
    public function clearCache(): void
    {
        Cache::tags(["user.{$this->user->id}"])->flush();
    }
}
