<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Account;
use App\Services\PlaidService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Account Balances Job
 * 
 * Fetches and updates account balances from Plaid for a given account.
 * This job is typically triggered by webhook events indicating balance changes.
 */
class SyncAccountBalances implements ShouldQueue
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
    public $timeout = 120;

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
            // Fetch balance from Plaid
            $balances = $plaidService->getBalance($this->account->plaid_access_token);

            // Find matching account in response
            $plaidAccount = collect($balances)->firstWhere('account_id', $this->account->plaid_account_id);

            if (!$plaidAccount) {
                Log::warning('Account not found in Plaid balance response', [
                    'account_id' => $this->account->id,
                    'plaid_account_id' => $this->account->plaid_account_id,
                ]);
                return;
            }

            // Store old balance for logging
            $oldBalance = $this->account->balance;
            $oldAvailableBalance = $this->account->available_balance;

            // Update account balances
            $this->account->update([
                'balance' => $plaidAccount['balances']['current'] ?? $this->account->balance,
                'available_balance' => $plaidAccount['balances']['available'] ?? $this->account->available_balance,
                'credit_limit' => $plaidAccount['balances']['limit'] ?? $this->account->credit_limit,
                'currency' => $plaidAccount['balances']['iso_currency_code'] ?? $this->account->currency ?? 'USD',
            ]);

            // Log balance changes
            if ($oldBalance != $this->account->balance || $oldAvailableBalance != $this->account->available_balance) {
                Log::info('Account balance updated', [
                    'account_id' => $this->account->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $this->account->balance,
                    'old_available' => $oldAvailableBalance,
                    'new_available' => $this->account->available_balance,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Balance sync failed', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}


