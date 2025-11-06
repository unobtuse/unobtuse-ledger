<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Account;
use App\Services\PlaidService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Account Liabilities Job
 * 
 * Fetches and updates liability data (due dates, interest rates, payment amounts)
 * from Plaid for eligible accounts (credit cards, loans, mortgages).
 */
class SyncAccountLiabilities implements ShouldQueue
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
            // Only sync liabilities for eligible account types
            if (!in_array($this->account->account_type, ['credit_card', 'loan'])) {
                return;
            }

            // Fetch liabilities from Plaid
            $liabilitiesData = $plaidService->getLiabilities($this->account->plaid_access_token);

            if (empty($liabilitiesData['accounts']) || empty($liabilitiesData['liabilities'])) {
                Log::debug('No liability data available for account', [
                    'account_id' => $this->account->id,
                    'account_type' => $this->account->account_type,
                ]);
                return;
            }

            // Find matching account in response
            $plaidAccount = collect($liabilitiesData['accounts'])->firstWhere('account_id', $this->account->plaid_account_id);

            if (!$plaidAccount) {
                Log::debug('Account not found in Plaid liabilities response', [
                    'account_id' => $this->account->id,
                    'plaid_account_id' => $this->account->plaid_account_id,
                ]);
                return;
            }

            // Find liability data for this account
            // Plaid returns liabilities as arrays (credit, mortgage, student, other)
            // Each liability object has an account_id field
            $liabilities = $liabilitiesData['liabilities'] ?? [];
            $accountLiabilities = null;

            // Extract credit card liability data
            if ($this->account->account_type === 'credit_card' && isset($liabilities['credit'])) {
                $accountLiabilities = collect($liabilities['credit'])->firstWhere('account_id', $this->account->plaid_account_id);
            }

            // Extract loan liability data (mortgage, student, auto, etc.)
            if ($this->account->account_type === 'loan' && !$accountLiabilities) {
                // Try mortgage first
                if (isset($liabilities['mortgage'])) {
                    $accountLiabilities = collect($liabilities['mortgage'])->firstWhere('account_id', $this->account->plaid_account_id);
                }
                // Try student loans
                if (!$accountLiabilities && isset($liabilities['student'])) {
                    $accountLiabilities = collect($liabilities['student'])->firstWhere('account_id', $this->account->plaid_account_id);
                }
                // Try other loans
                if (!$accountLiabilities && isset($liabilities['other'])) {
                    $accountLiabilities = collect($liabilities['other'])->firstWhere('account_id', $this->account->plaid_account_id);
                }
            }

            if (!$accountLiabilities) {
                Log::debug('No liability data found for account', [
                    'account_id' => $this->account->id,
                    'plaid_account_id' => $this->account->plaid_account_id,
                ]);
                return;
            }

            // Prepare update data
            $updateData = [];

            // Extract credit card liability data
            if ($this->account->account_type === 'credit_card') {
                // Due date - Plaid provides next_payment_due_date for credit cards
                if (isset($accountLiabilities['next_payment_due_date'])) {
                    $dueDate = Carbon::parse($accountLiabilities['next_payment_due_date']);
                    $updateData['payment_due_date'] = $dueDate->format('Y-m-d');
                    $updateData['payment_due_date_source'] = 'plaid';
                    $updateData['payment_due_day'] = $dueDate->day;
                } elseif (isset($accountLiabilities['last_payment_date'])) {
                    // Fallback: calculate from last payment date
                    $lastPaymentDate = Carbon::parse($accountLiabilities['last_payment_date']);
                    $nextDueDate = $lastPaymentDate->copy()->addMonth();
                    $updateData['payment_due_date'] = $nextDueDate->format('Y-m-d');
                    $updateData['payment_due_date_source'] = 'plaid';
                    $updateData['payment_due_day'] = $nextDueDate->day;
                }
                
                // Payment amounts
                if (isset($accountLiabilities['minimum_payment_amount'])) {
                    $updateData['minimum_payment_amount'] = $accountLiabilities['minimum_payment_amount'];
                }
                
                // APR (interest rate)
                if (isset($accountLiabilities['aprs']) && is_array($accountLiabilities['aprs']) && count($accountLiabilities['aprs']) > 0) {
                    $primaryApr = $accountLiabilities['aprs'][0];
                    if (isset($primaryApr['apr_percentage'])) {
                        $updateData['interest_rate'] = $primaryApr['apr_percentage'];
                        $updateData['interest_rate_type'] = $primaryApr['apr_type'] ?? null;
                        $updateData['interest_rate_source'] = 'plaid';
                    }
                }
            }

            // Extract loan liability data (mortgage, student, auto, etc.)
            if ($this->account->account_type === 'loan') {
                // Due date - Plaid provides next_payment_due_date for loans
                if (isset($accountLiabilities['next_payment_due_date'])) {
                    $dueDate = Carbon::parse($accountLiabilities['next_payment_due_date']);
                    $updateData['payment_due_date'] = $dueDate->format('Y-m-d');
                    $updateData['payment_due_date_source'] = 'plaid';
                    $updateData['payment_due_day'] = $dueDate->day;
                } elseif (isset($accountLiabilities['last_payment_date'])) {
                    // Fallback: calculate from last payment date
                    $lastPaymentDate = Carbon::parse($accountLiabilities['last_payment_date']);
                    $nextDueDate = $lastPaymentDate->copy()->addMonth();
                    $updateData['payment_due_date'] = $nextDueDate->format('Y-m-d');
                    $updateData['payment_due_date_source'] = 'plaid';
                    $updateData['payment_due_day'] = $nextDueDate->day;
                }
                
                // Payment amount
                if (isset($accountLiabilities['last_payment_amount'])) {
                    $updateData['next_payment_amount'] = $accountLiabilities['last_payment_amount'];
                } elseif (isset($accountLiabilities['minimum_payment_amount'])) {
                    $updateData['next_payment_amount'] = $accountLiabilities['minimum_payment_amount'];
                }
                
                // Interest rate
                if (isset($accountLiabilities['interest_rate'])) {
                    if (is_array($accountLiabilities['interest_rate'])) {
                        $updateData['interest_rate'] = $accountLiabilities['interest_rate']['percentage'] ?? null;
                        $updateData['interest_rate_type'] = $accountLiabilities['interest_rate']['type'] ?? null;
                    } else {
                        $updateData['interest_rate'] = $accountLiabilities['interest_rate'];
                    }
                    $updateData['interest_rate_source'] = 'plaid';
                }
            }

            // Only update if we have data and source is 'plaid' or null (allow manual overrides)
            if (!empty($updateData) && ($this->account->payment_due_date_source === null || $this->account->payment_due_date_source === 'plaid')) {
                $this->account->update($updateData);

                Log::info('Account liabilities updated', [
                    'account_id' => $this->account->id,
                    'updated_fields' => array_keys($updateData),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Liabilities sync failed', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't throw - this is not critical, account can still function without liability data
        }
    }
}
