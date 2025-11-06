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
 * Match Bill Payments Job
 * 
 * Automatically matches transactions to bills based on:
 * - Merchant name similarity
 * - Amount similarity (within tolerance)
 * - Date proximity to bill due date
 * 
 * When a match is found, the bill is automatically marked as paid
 * and the transaction is linked to the bill.
 */
class MatchBillPayments implements ShouldQueue
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
            Log::info('Starting bill payment matching', ['user_id' => $this->user->id]);

            // Get unpaid bills
            $bills = Bill::where('user_id', $this->user->id)
                ->whereIn('payment_status', ['upcoming', 'due', 'overdue'])
                ->get();

            if ($bills->isEmpty()) {
                Log::info('No unpaid bills to match', ['user_id' => $this->user->id]);
                return;
            }

            // Get recent debit transactions that aren't already linked to a bill
            $transactions = Transaction::where('user_id', $this->user->id)
                ->where('transaction_type', 'debit')
                ->whereNull('bill_id')
                ->where('transaction_date', '>=', now()->subMonths(3)) // Look back 3 months
                ->orderBy('transaction_date', 'desc')
                ->get();

            if ($transactions->isEmpty()) {
                Log::info('No transactions to match', ['user_id' => $this->user->id]);
                return;
            }

            $matched = 0;

            // Try to match each transaction to a bill
            foreach ($transactions as $transaction) {
                $bestMatch = $this->findBestBillMatch($transaction, $bills);

                if ($bestMatch) {
                    $this->linkTransactionToBill($transaction, $bestMatch);
                    $matched++;
                }
            }

            Log::info('Bill payment matching completed', [
                'user_id' => $this->user->id,
                'matched' => $matched,
            ]);

        } catch (\Exception $e) {
            Log::error('Bill payment matching failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Find the best matching bill for a transaction.
     *
     * @param Transaction $transaction
     * @param \Illuminate\Support\Collection $bills
     * @return Bill|null
     */
    protected function findBestBillMatch(Transaction $transaction, $bills): ?Bill
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($bills as $bill) {
            $score = $this->calculateMatchScore($transaction, $bill);

            if ($score > $bestScore && $score >= 60) { // Minimum 60% confidence
                $bestScore = $score;
                $bestMatch = $bill;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate match score between transaction and bill (0-100).
     *
     * @param Transaction $transaction
     * @param Bill $bill
     * @return float
     */
    protected function calculateMatchScore(Transaction $transaction, Bill $bill): float
    {
        $score = 0;
        $maxScore = 100;

        // 1. Merchant name similarity (40 points)
        $merchantScore = $this->calculateMerchantSimilarity(
            $transaction->merchant_name ?? $transaction->name,
            $bill->name
        );
        $score += $merchantScore * 0.4;

        // 2. Amount similarity (30 points)
        $transactionAmount = abs((float) $transaction->amount);
        $billAmount = abs((float) $bill->amount);
        $amountScore = $this->calculateAmountSimilarity($transactionAmount, $billAmount);
        $score += $amountScore * 0.3;

        // 3. Date proximity (30 points)
        $dateScore = $this->calculateDateProximity(
            $transaction->transaction_date,
            $bill->next_due_date
        );
        $score += $dateScore * 0.3;

        return round($score, 2);
    }

    /**
     * Calculate merchant name similarity using normalized comparison.
     *
     * @param string|null $transactionMerchant
     * @param string $billName
     * @return float Score from 0-100
     */
    protected function calculateMerchantSimilarity(?string $transactionMerchant, string $billName): float
    {
        if (!$transactionMerchant) {
            return 0;
        }

        $normalizedTransaction = $this->normalizeMerchantName($transactionMerchant);
        $normalizedBill = $this->normalizeMerchantName($billName);

        // Exact match
        if ($normalizedTransaction === $normalizedBill) {
            return 100;
        }

        // Check if one contains the other
        if (str_contains($normalizedTransaction, $normalizedBill) || 
            str_contains($normalizedBill, $normalizedTransaction)) {
            return 85;
        }

        // Calculate similarity using Levenshtein distance
        $maxLength = max(strlen($normalizedTransaction), strlen($normalizedBill));
        if ($maxLength === 0) {
            return 0;
        }

        $distance = levenshtein($normalizedTransaction, $normalizedBill);
        $similarity = (1 - ($distance / $maxLength)) * 100;

        return max(0, min(100, $similarity));
    }

    /**
     * Normalize merchant name for comparison.
     *
     * @param string $merchantName
     * @return string
     */
    protected function normalizeMerchantName(string $merchantName): string
    {
        $normalized = strtolower($merchantName);
        
        // Remove common suffixes
        $normalized = preg_replace('/\s+(inc|llc|corp|ltd|co|company)\.?$/i', '', $normalized);
        
        // Remove special characters
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
        
        // Normalize whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Calculate amount similarity score.
     *
     * @param float $transactionAmount
     * @param float $billAmount
     * @return float Score from 0-100
     */
    protected function calculateAmountSimilarity(float $transactionAmount, float $billAmount): float
    {
        if ($billAmount == 0) {
            return 0;
        }

        $difference = abs($transactionAmount - $billAmount);
        $percentageDifference = ($difference / $billAmount) * 100;

        // Perfect match = 100 points
        if ($percentageDifference == 0) {
            return 100;
        }

        // Within 1% = 95 points
        if ($percentageDifference <= 1) {
            return 95;
        }

        // Within 5% = 80 points
        if ($percentageDifference <= 5) {
            return 80;
        }

        // Within 10% = 60 points
        if ($percentageDifference <= 10) {
            return 60;
        }

        // Within 20% = 40 points
        if ($percentageDifference <= 20) {
            return 40;
        }

        // More than 20% difference = 0 points
        return 0;
    }

    /**
     * Calculate date proximity score.
     *
     * @param \Carbon\Carbon|string $transactionDate
     * @param \Carbon\Carbon|string $billDueDate
     * @return float Score from 0-100
     */
    protected function calculateDateProximity($transactionDate, $billDueDate): float
    {
        $transactionDate = Carbon::parse($transactionDate);
        $billDueDate = Carbon::parse($billDueDate);
        
        $daysDifference = abs($transactionDate->diffInDays($billDueDate));

        // Transaction on due date = 100 points
        if ($daysDifference == 0) {
            return 100;
        }

        // Within 3 days = 90 points
        if ($daysDifference <= 3) {
            return 90;
        }

        // Within 7 days = 75 points
        if ($daysDifference <= 7) {
            return 75;
        }

        // Within 14 days = 60 points
        if ($daysDifference <= 14) {
            return 60;
        }

        // Within 30 days = 40 points
        if ($daysDifference <= 30) {
            return 40;
        }

        // More than 30 days = 0 points
        return 0;
    }

    /**
     * Link transaction to bill and mark bill as paid.
     *
     * @param Transaction $transaction
     * @param Bill $bill
     * @return void
     */
    protected function linkTransactionToBill(Transaction $transaction, Bill $bill): void
    {
        // Link transaction to bill
        $transaction->update([
            'bill_id' => $bill->id,
        ]);

        // Mark bill as paid if it's not already paid
        if ($bill->payment_status !== 'paid') {
            $bill->markAsPaid(abs((float) $transaction->amount));
            
            Log::info('Bill automatically marked as paid', [
                'bill_id' => $bill->id,
                'transaction_id' => $transaction->id,
                'amount' => abs((float) $transaction->amount),
            ]);
        }
    }
}

