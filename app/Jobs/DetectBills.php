<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Detect Bills Job
 * 
 * Analyzes user transactions to automatically detect recurring bills
 * based on patterns (same merchant, similar amount, regular frequency).
 */
class DetectBills implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting bill detection', ['user_id' => $this->user->id]);

            // Get transactions from last 6 months
            $transactions = $this->user->transactions()
                ->where('transaction_type', 'debit')
                ->where('transaction_date', '>=', now()->subMonths(6))
                ->orderBy('transaction_date', 'asc')
                ->get();

            // Group transactions by merchant name
            $groupedTransactions = $transactions->groupBy(function ($transaction) {
                return $this->normalizeMerchantName($transaction->merchant_name ?? $transaction->name);
            });

            $detected = 0;

            // Analyze each merchant group
            foreach ($groupedTransactions as $merchant => $merchantTransactions) {
                if ($merchantTransactions->count() < 2) {
                    continue; // Need at least 2 transactions to detect pattern
                }

                $pattern = $this->detectRecurringPattern($merchantTransactions);

                if ($pattern && $pattern['confidence'] >= 70) {
                    // Check if bill already exists
                    $existingBill = Bill::where('user_id', $this->user->id)
                        ->where('name', $pattern['name'])
                        ->where('auto_detected', true)
                        ->first();

                    if (!$existingBill) {
                        $this->createBill($pattern, $merchantTransactions->first());
                        $detected++;
                    }
                }
            }

            Log::info('Bill detection completed', [
                'user_id' => $this->user->id,
                'detected' => $detected,
            ]);

        } catch (\Exception $e) {
            Log::error('Bill detection failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Normalize merchant name for grouping.
     *
     * @param string|null $merchantName
     * @return string
     */
    protected function normalizeMerchantName(?string $merchantName): string
    {
        if (!$merchantName) {
            return 'unknown';
        }

        // Remove common suffixes and normalize
        $normalized = strtolower($merchantName);
        $normalized = preg_replace('/\s+(inc|llc|corp|ltd|co)\.?$/i', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Detect recurring pattern in transactions.
     *
     * @param \Illuminate\Support\Collection $transactions
     * @return array|null
     */
    protected function detectRecurringPattern($transactions): ?array
    {
        if ($transactions->count() < 2) {
            return null;
        }

        // Calculate average amount
        $avgAmount = $transactions->avg('amount');
        $amounts = $transactions->pluck('amount')->toArray();
        
        // Calculate amount variance (should be low for bills)
        $variance = $this->calculateVariance($amounts);
        $amountConsistency = $variance < ($avgAmount * 0.1) ? 90 : 60; // 10% variance threshold

        // Calculate time intervals between transactions
        $dates = $transactions->pluck('transaction_date')->map(fn($d) => Carbon::parse($d))->toArray();
        $intervals = [];
        
        for ($i = 1; $i < count($dates); $i++) {
            $intervals[] = $dates[$i]->diffInDays($dates[$i - 1]);
        }

        if (empty($intervals)) {
            return null;
        }

        $avgInterval = array_sum($intervals) / count($intervals);

        // Determine frequency
        $frequency = $this->determineFrequency($avgInterval);
        
        // Calculate frequency consistency
        $intervalVariance = $this->calculateVariance($intervals);
        $frequencyConsistency = $intervalVariance < 7 ? 90 : 60; // 7 days variance threshold

        // Overall confidence score
        $confidence = ($amountConsistency + $frequencyConsistency) / 2;

        // Estimate next due date
        $lastDate = $dates[count($dates) - 1];
        $nextDueDate = $this->calculateNextDueDate($lastDate, $frequency);

        return [
            'name' => $transactions->first()->merchant_name ?? $transactions->first()->name,
            'amount' => round($avgAmount, 2),
            'frequency' => $frequency,
            'next_due_date' => $nextDueDate,
            'confidence' => round($confidence),
            'transaction_count' => $transactions->count(),
            'avg_interval' => round($avgInterval),
        ];
    }

    /**
     * Calculate variance of an array of numbers.
     *
     * @param array $numbers
     * @return float
     */
    protected function calculateVariance(array $numbers): float
    {
        if (count($numbers) < 2) {
            return 0;
        }

        $mean = array_sum($numbers) / count($numbers);
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $numbers);
        
        return sqrt(array_sum($squaredDiffs) / count($numbers));
    }

    /**
     * Determine frequency based on average interval.
     *
     * @param float $avgInterval
     * @return string
     */
    protected function determineFrequency(float $avgInterval): string
    {
        return match(true) {
            $avgInterval >= 350 => 'annual',
            $avgInterval >= 85 => 'quarterly',
            $avgInterval >= 28 && $avgInterval <= 31 => 'monthly',
            $avgInterval >= 13 && $avgInterval <= 15 => 'biweekly',
            $avgInterval >= 6 && $avgInterval <= 8 => 'weekly',
            default => 'monthly',
        };
    }

    /**
     * Calculate next due date based on frequency.
     *
     * @param Carbon $lastDate
     * @param string $frequency
     * @return Carbon
     */
    protected function calculateNextDueDate(Carbon $lastDate, string $frequency): Carbon
    {
        return match($frequency) {
            'weekly' => $lastDate->copy()->addWeek(),
            'biweekly' => $lastDate->copy()->addWeeks(2),
            'monthly' => $lastDate->copy()->addMonth(),
            'quarterly' => $lastDate->copy()->addMonths(3),
            'annual' => $lastDate->copy()->addYear(),
            default => $lastDate->copy()->addMonth(),
        };
    }

    /**
     * Create a bill from detected pattern.
     *
     * @param array $pattern
     * @param Transaction $sourceTransaction
     * @return Bill
     */
    protected function createBill(array $pattern, Transaction $sourceTransaction): Bill
    {
        $category = $this->inferCategory($pattern['name'], $sourceTransaction->category);

        return Bill::create([
            'user_id' => $this->user->id,
            'account_id' => $sourceTransaction->account_id,
            'name' => $pattern['name'],
            'amount' => $pattern['amount'],
            'due_date' => $pattern['next_due_date'],
            'next_due_date' => $pattern['next_due_date'],
            'frequency' => $pattern['frequency'],
            'category' => $category,
            'payment_status' => 'upcoming',
            'auto_detected' => true,
            'source_transaction_id' => $sourceTransaction->id,
            'detection_confidence' => $pattern['confidence'],
            'reminder_enabled' => true,
            'reminder_days_before' => 3,
            'priority' => 'medium',
        ]);
    }

    /**
     * Infer bill category from name and transaction category.
     *
     * @param string $name
     * @param string|null $transactionCategory
     * @return string
     */
    protected function inferCategory(string $name, ?string $transactionCategory): string
    {
        $nameLower = strtolower($name);

        // Check for common bill keywords
        $categoryMap = [
            'rent' => ['rent', 'apartment', 'housing'],
            'utilities' => ['electric', 'gas', 'water', 'utility', 'power'],
            'internet' => ['internet', 'comcast', 'spectrum', 'att', 'verizon'],
            'phone' => ['phone', 'mobile', 't-mobile', 'sprint'],
            'insurance' => ['insurance', 'geico', 'state farm', 'allstate'],
            'subscription' => ['netflix', 'spotify', 'hulu', 'amazon prime', 'disney'],
            'loan' => ['loan', 'mortgage', 'credit'],
        ];

        foreach ($categoryMap as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($nameLower, $keyword)) {
                    return $category;
                }
            }
        }

        return 'other';
    }
}
