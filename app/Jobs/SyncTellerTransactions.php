<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\TellerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Teller Transactions Job
 *
 * Fetches transactions from Teller for a given account and stores them
 * in the database. Handles pagination and duplicate detection.
 */
class SyncTellerTransactions implements ShouldQueue
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
    public function handle(TellerService $tellerService): void
    {
        try {
            // Update sync status
            $this->account->update(['sync_status' => 'syncing']);

            // Get teller credentials
            $accessToken = $this->account->teller_token;
            $accountId = $this->account->teller_account_id;

            if (!$accessToken || !$accountId) {
                throw new \RuntimeException('Missing Teller credentials for account');
            }

            // Fetch transactions from Teller
            // Teller returns most recent transactions first
            $response = $tellerService->getTransactions($accessToken, $accountId, [
                'count' => 300, // Get up to 300 most recent transactions
            ]);

            if (!$response['success']) {
                throw new \Exception($response['error'] ?? 'Failed to fetch transactions from Teller');
            }

            $transactions = $response['transactions'] ?? [];
            $synced = 0;
            $skipped = 0;

            // Process each transaction
            foreach ($transactions as $tellerTransaction) {
                // Check if transaction already exists by Teller transaction ID
                $exists = Transaction::where('teller_transaction_id', $tellerTransaction['id'] ?? null)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Create transaction record
                $this->createTransaction($tellerTransaction);
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
                // Also match payments to existing bills
                MatchBillPayments::dispatch($this->account->user);
            }

            Log::info('Teller transaction sync completed', [
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

            Log::error('Teller transaction sync failed', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create transaction from Teller data.
     *
     * Teller transaction format:
     * {
     *   "id": "txn_...",
     *   "account_id": "acc_...",
     *   "amount": "-30.50",
     *   "date": "2024-11-17",
     *   "description": "Starbucks",
     *   "details": {
     *     "category": "Food & Drink",
     *     "counterparty": {
     *       "name": "Starbucks"
     *     }
     *   },
     *   "running_balance": "1234.50",
     *   "status": "posted"
     * }
     *
     * @param array $tellerTransaction
     * @return Transaction
     */
    protected function createTransaction(array $tellerTransaction): Transaction
    {
        $amount = (float) ($tellerTransaction['amount'] ?? 0);
        $transactionType = $amount > 0 ? 'credit' : 'debit';

        $details = $tellerTransaction['details'] ?? [];
        $counterparty = $details['counterparty'] ?? [];

        $category = $this->extractCategory($details);

        return Transaction::create([
            'user_id' => $this->account->user_id,
            'account_id' => $this->account->id,
            'teller_transaction_id' => $tellerTransaction['id'] ?? null,
            'name' => $tellerTransaction['description'] ?? 'Transaction',
            'merchant_name' => $counterparty['name'] ?? null,
            'amount' => $amount,
            'iso_currency_code' => 'USD', // Teller defaults to USD
            'transaction_date' => $tellerTransaction['date'] ?? now()->format('Y-m-d'),
            'authorized_date' => null,
            'posted_date' => $tellerTransaction['date'] ?? now()->format('Y-m-d'),
            'category' => $category,
            'transaction_type' => $transactionType,
            'pending' => ($tellerTransaction['status'] ?? 'posted') === 'pending',
            'payment_channel' => null, // Teller doesn't provide this
            'metadata' => $tellerTransaction,
        ]);
    }

    /**
     * Extract simplified category from Teller categories.
     *
     * @param array $details
     * @return string|null
     */
    protected function extractCategory(array $details): ?string
    {
        $tellerCategory = $details['category'] ?? null;

        if (!$tellerCategory) {
            return null;
        }

        // Map Teller categories to our simplified categories
        $categoryMap = [
            'food & drink' => 'food',
            'travel' => 'travel',
            'shopping' => 'shopping',
            'entertainment' => 'entertainment',
            'personal services' => 'services',
            'healthcare' => 'healthcare',
            'bill payment' => 'bills',
            'transfer' => 'transfer',
            'fee' => 'fees',
        ];

        $tellerCategoryLower = strtolower($tellerCategory);

        foreach ($categoryMap as $key => $value) {
            if (str_contains($tellerCategoryLower, $key)) {
                return $value;
            }
        }

        return 'other';
    }
}

