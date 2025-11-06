<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\PlaidService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Refresh All Account Balances Job
 * 
 * Refreshes balances for all active accounts belonging to a user.
 * Dispatched automatically on login to ensure fresh balance data.
 */
class RefreshAllAccountBalances implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PlaidService $plaidService): void
    {
        $accounts = $this->user->accounts()
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return;
        }

        Log::info('Refreshing account balances on login', [
            'user_id' => $this->user->id,
            'account_count' => $accounts->count(),
        ]);

        foreach ($accounts as $account) {
            try {
                $balances = $plaidService->getBalance($account->plaid_access_token);

                foreach ($balances as $balance) {
                    if (($balance['account_id'] ?? null) === $account->plaid_account_id) {
                        $account->update([
                            'balance' => $balance['balances']['current'] ?? 0,
                            'available_balance' => $balance['balances']['available'] ?? null,
                            'credit_limit' => $balance['balances']['limit'] ?? null,
                            'last_synced_at' => now(),
                            'sync_status' => 'synced',
                        ]);

                        Log::debug('Account balance refreshed on login', [
                            'user_id' => $this->user->id,
                            'account_id' => $account->id,
                            'balance' => $account->balance,
                        ]);

                        break;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to refresh account balance on login', [
                    'user_id' => $this->user->id,
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);

                // Continue with next account instead of failing entire job
                continue;
            }
        }

        Log::info('Account balance refresh completed on login', [
            'user_id' => $this->user->id,
        ]);
    }
}


