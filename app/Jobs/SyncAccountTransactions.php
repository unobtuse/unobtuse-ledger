<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\PlaidService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Account Transactions Job
 * 
 * Fetches transactions from Plaid for a given account and stores them
 * in the database. Handles pagination and duplicate detection.
 */
class SyncAccountTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Account $account
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PlaidService $plaidService): void
    {
        try {
            // Update sync status
            $this->account->update(['sync_status' => 'syncing']);

            // Determine date range
            $endDate = now()->format('Y-m-d');
            $startDate = $this->getStartDate();

            // Fetch transactions from Plaid
            $response = $plaidService->getTransactions(
                $this->account->plaid_access_token,
                $startDate,
                $endDate
            );

            $transactions = $response['transactions'];
            $synced = 0;
            $skipped = 0;

            // Process each transaction
            foreach ($transactions as $plaidTransaction) {
                // Check if transaction already exists
                $exists = Transaction::where('plaid_transaction_id', $plaidTransaction['transaction_id'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Create transaction record
                $this->createTransaction($plaidTransaction);
                $synced++;
            }

            // Update account sync status
            $this->account->update([
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'sync_error' => null,
            ]);

            // Dispatch bill detection after successful sync
            if ($synced > 0) {
                DetectBills::dispatch($this->account->user);
            }

            Log::info('Transaction sync completed', [
                'account_id' => $this->account->id,
                'synced' => $synced,
                'skipped' => $skipped,
            ]);

        } catch (\Exception $e) {
            // Update sync status with error
            $this->account->update([
                'sync_status' => 'failed',
                'sync_error' => $e->getMessage(),
            ]);

            Log::error('Transaction sync failed', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get start date for transaction sync.
     *
     * @return string
     */
    protected function getStartDate(): string
    {
        // Get the last transaction date or default to 2 years ago
        $lastTransaction = $this->account->transactions()
            ->orderBy('transaction_date', 'desc')
            ->first();

        if ($lastTransaction) {
            return $lastTransaction->transaction_date->subDays(7)->format('Y-m-d');
        }

        // Default to configured days or 730 days (2 years)
        $days = config('plaid.sync.transaction_days', 730);
        return now()->subDays($days)->format('Y-m-d');
    }

    /**
     * Create transaction from Plaid data.
     *
     * @param array $plaidTransaction
     * @return Transaction
     */
    protected function createTransaction(array $plaidTransaction): Transaction
    {
        // Determine transaction type
        $amount = $plaidTransaction['amount'];
        $transactionType = $amount > 0 ? 'debit' : 'credit';

        // Extract category
        $category = $this->extractCategory($plaidTransaction);

        return Transaction::create([
            'user_id' => $this->account->user_id,
            'account_id' => $this->account->id,
            'plaid_transaction_id' => $plaidTransaction['transaction_id'],
            'name' => $plaidTransaction['name'],
            'merchant_name' => $plaidTransaction['merchant_name'] ?? null,
            'amount' => abs($amount),
            'iso_currency_code' => $plaidTransaction['iso_currency_code'] ?? 'USD',
            'transaction_date' => $plaidTransaction['date'],
            'authorized_date' => $plaidTransaction['authorized_date'] ?? null,
            'posted_date' => $plaidTransaction['date'],
            'category' => $category,
            'plaid_categories' => $plaidTransaction['category'] ?? null,
            'category_id' => $plaidTransaction['category_id'] ?? null,
            'transaction_type' => $transactionType,
            'pending' => $plaidTransaction['pending'] ?? false,
            'location_address' => $plaidTransaction['location']['address'] ?? null,
            'location_city' => $plaidTransaction['location']['city'] ?? null,
            'location_region' => $plaidTransaction['location']['region'] ?? null,
            'location_postal_code' => $plaidTransaction['location']['postal_code'] ?? null,
            'location_country' => $plaidTransaction['location']['country'] ?? null,
            'location_lat' => $plaidTransaction['location']['lat'] ?? null,
            'location_lon' => $plaidTransaction['location']['lon'] ?? null,
            'payment_channel' => $plaidTransaction['payment_channel'] ?? null,
            'metadata' => $plaidTransaction,
        ]);
    }

    /**
     * Extract simplified category from Plaid categories.
     *
     * @param array $plaidTransaction
     * @return string|null
     */
    protected function extractCategory(array $plaidTransaction): ?string
    {
        $categories = $plaidTransaction['category'] ?? [];

        if (empty($categories)) {
            return null;
        }

        // Map Plaid categories to our simplified categories
        $primaryCategory = strtolower($categories[0]);

        $categoryMap = [
            'food and drink' => 'food',
            'travel' => 'travel',
            'shops' => 'shopping',
            'recreation' => 'entertainment',
            'service' => 'services',
            'healthcare' => 'healthcare',
            'payment' => 'bills',
            'transfer' => 'transfer',
            'bank fees' => 'fees',
        ];

        foreach ($categoryMap as $key => $value) {
            if (str_contains($primaryCategory, $key)) {
                return $value;
            }
        }

        return 'other';
    }
}
