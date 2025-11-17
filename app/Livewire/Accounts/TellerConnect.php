<?php

declare(strict_types=1);

namespace App\Livewire\Accounts;

use App\Models\Account;
use App\Models\Institution;
use App\Models\User;
use App\Services\TellerService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

/**
 * Teller Connect Component
 *
 * Manages the Teller Connect widget integration for linking bank accounts.
 * Handles initialization, token exchange, and account creation.
 */
class TellerConnect extends Component
{
    public User $user;
    public bool $showConnect = false;
    public string $connectConfig = '';
    public bool $isLinking = false;
    public ?string $error = null;
    public ?string $success = null;

    private ?TellerService $tellerService = null;

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->tellerService = app(TellerService::class);
        
        // Initialize Teller Connect config
        $config = $this->getTellerService()->initializeTellerConnect($this->user);
        $this->connectConfig = json_encode($config);
    }

    /**
     * Get the TellerService instance
     */
    private function getTellerService(): TellerService
    {
        if ($this->tellerService === null) {
            $this->tellerService = app(TellerService::class);
        }
        return $this->tellerService;
    }

    /**
     * Initialize Teller Connect widget
     */
    #[\Livewire\Attributes\On('initiateTellerConnect')]
    public function initiateTellerConnect(): void
    {
        try {
            $this->isLinking = true;
            $this->error = null;
            $this->success = null;

            // Get configuration for Teller Connect
            $config = $this->getTellerService()->initializeTellerConnect($this->user);

            // Store config as JSON for JavaScript
            $this->connectConfig = json_encode($config);
            $this->showConnect = true;

            Log::info('Teller Connect initialized', ['user_id' => $this->user->id]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Teller Connect', [
                'error' => $e->getMessage(),
                'user_id' => $this->user->id,
            ]);

            $this->error = 'Failed to initialize bank linking. Please try again.';
            $this->isLinking = false;
        }
    }

    /**
     * Handle successful Teller Connect enrollment
     * Called via JavaScript after user completes Teller Connect flow
     * 
     * Teller Connect returns the accessToken DIRECTLY - no exchange needed!
     */
    #[\Livewire\Attributes\On('tellerEnrollmentSuccess')]
    public function handleEnrollmentSuccess($accessToken = null, $enrollmentId = null, $institutionName = null, $enrollmentData = null): void
    {
        try {
            $this->isLinking = true;
            
            // Log what we received
            Log::info('Teller enrollment received - RAW PARAMS', [
                'user_id' => $this->user->id,
                'accessToken' => $accessToken,
                'enrollmentId' => $enrollmentId,
                'institutionName' => $institutionName,
                'has_accessToken' => !empty($accessToken),
            ]);

            if (empty($accessToken)) {
                Log::error('No access token in Livewire handler', [
                    'accessToken_param' => $accessToken,
                    'enrollmentId_param' => $enrollmentId,
                ]);
                throw new \Exception('No access token received from Teller Connect');
            }

            // Get all accounts using the access token
            $accountsResult = $this->getTellerService()->getAccounts($accessToken);
            
            if (!$accountsResult['success'] || empty($accountsResult['accounts'])) {
                throw new \Exception('Failed to retrieve accounts from Teller');
            }

            $createdAccounts = [];
            $errors = [];

            // Process ALL accounts returned by Teller
            foreach ($accountsResult['accounts'] as $tellerAccount) {
                try {
                    $tellerAccountId = $tellerAccount['id'];

                    // Get detailed account information
                    $accountDetails = $this->getTellerService()->getAccountDetails($accessToken, $tellerAccountId);

                    if (!$accountDetails['success']) {
                        Log::warning('Failed to get account details', [
                            'teller_account_id' => $tellerAccountId,
                            'user_id' => $this->user->id,
                        ]);
                        $errors[] = "Failed to retrieve details for account {$tellerAccountId}";
                        continue;
                    }

                    $account = $accountDetails['account'];

                    // Extract institution ID from account data
                    $institutionId = $account['institution']['id'] ?? null;
                    $institutionNameFromAccount = $account['institution']['name'] ?? $institutionName;

                    // Note: Teller Institution API may not work in development mode or may return 404
                    // We'll skip fetching for now and implement fallback in Phase 4
                    // If $institutionId is available, it will be stored for later use
                    if ($institutionId) {
                        Log::info('Institution ID available for caching', [
                            'institution_id' => $institutionId,
                            'note' => 'Will be cached later if API becomes available',
                        ]);
                    }

                    // Extract account information from Teller's response
                    $accountName = $account['name'] ?? ($institutionNameFromAccount . ' Account');
                    $accountType = strtolower($account['subtype'] ?? $account['type'] ?? 'checking');
                    $lastFour = $account['last_four'] ?? $account['mask'] ?? null;

                    // Check if account already exists (prevent duplicates)
                    $existingAccount = Account::where('teller_account_id', $tellerAccountId)
                        ->where('user_id', $this->user->id)
                        ->first();

                    if ($existingAccount) {
                        Log::info('Account already exists, skipping', [
                            'teller_account_id' => $tellerAccountId,
                            'user_id' => $this->user->id,
                        ]);
                        continue;
                    }

                    // Create account record in our database
                    $dbAccount = Account::create([
                        'user_id' => $this->user->id,
                        'account_name' => $accountName,
                        'account_type' => $accountType,
                        'mask' => $lastFour, // Store last 4 digits in mask field for UI display
                        'institution_id' => $institutionId, // Link to institution
                        'institution_name' => $institutionNameFromAccount,
                        'teller_token' => $accessToken,
                        'teller_account_id' => $tellerAccountId,
                        'balance' => 0, // Will be synced immediately below
                        'is_active' => true,
                    ]);

                    Log::info('Account created from Teller', [
                        'user_id' => $this->user->id,
                        'account_id' => $dbAccount->id,
                        'teller_account_id' => $tellerAccountId,
                        'account_name' => $accountName,
                        'last_four' => $lastFour,
                    ]);

                    // Sync initial balance and extract credit card information
                    $balanceResult = $this->getTellerService()->getBalance($accessToken, $tellerAccountId);

                    if ($balanceResult['success'] && isset($balanceResult['balance'])) {
                        $balance = $balanceResult['balance'];
                        $updateData = [];

                        // Extract balance data using account type-aware logic
                        $balanceData = $this->getTellerService()->extractBalanceForAccountType(
                            $balance,
                            $accountType
                        );

                        // Merge balance data into update array
                        $updateData = array_merge($updateData, $balanceData);

                        // Extract credit card specific fields (liabilities) with source tracking
                        if ($accountType === 'credit_card' || $accountType === 'credit') {
                            // Payment due date
                            if (isset($balance['payment_due_date'])) {
                                $updateData['payment_due_date'] = $balance['payment_due_date'];
                                $updateData['payment_due_date_source'] = 'teller';
                            }

                            // Minimum payment amount
                            if (isset($balance['minimum_payment_amount'])) {
                                $updateData['minimum_payment_amount'] = $balance['minimum_payment_amount'];
                            }

                            // Interest rate
                            if (isset($balance['interest_rate'])) {
                                $updateData['interest_rate'] = $balance['interest_rate'];
                                $updateData['interest_rate_source'] = 'teller';
                            }
                        }

                        if (!empty($updateData)) {
                            $dbAccount->update($updateData);
                        }

                        Log::info('Account balance synced', [
                            'account_id' => $dbAccount->id,
                            'account_type' => $accountType,
                            'balance_amount' => $updateData['balance'] ?? null,
                            'available_balance' => $updateData['available_balance'] ?? null,
                            'credit_limit' => $updateData['credit_limit'] ?? null,
                        ]);
                    }

                    // Dispatch job to sync initial transactions
                    \App\Jobs\SyncTellerTransactions::dispatch($dbAccount);

                    $createdAccounts[] = $dbAccount;
                } catch (\Exception $e) {
                    Log::error('Failed to process account from Teller', [
                        'teller_account_id' => $tellerAccount['id'] ?? 'unknown',
                        'user_id' => $this->user->id,
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = "Failed to process account: " . $e->getMessage();
                }
            }

            if (empty($createdAccounts)) {
                throw new \Exception('No accounts were successfully created. ' . implode('; ', $errors));
            }

            Log::info('Teller enrollment completed', [
                'user_id' => $this->user->id,
                'accounts_created' => count($createdAccounts),
                'errors' => $errors,
            ]);

            $this->showConnect = false;
            $this->isLinking = false;
            $accountCount = count($createdAccounts);
            $this->success = "Successfully linked {$accountCount} account" . ($accountCount !== 1 ? 's' : '') . "!";

            // Refresh the page to show new account
            $this->dispatch('refreshAccounts');
        } catch (\Exception $e) {
            Log::error('Failed to handle Teller enrollment', [
                'error' => $e->getMessage(),
                'user_id' => $this->user->id,
            ]);

            $this->error = 'Failed to link account: ' . $e->getMessage();
            $this->isLinking = false;
        }
    }

    /**
     * Handle Teller Connect errors
     * Called via JavaScript if user cancels or encounters error
     */
    #[\Livewire\Attributes\On('tellerEnrollmentError')]
    public function handleEnrollmentError(...$params): void
    {
        // Extract error data from event (Livewire passes params as variadic)
        $data = $params[0] ?? [];
        $errorCode = is_array($data) ? ($data['errorCode'] ?? 'unknown_error') : 'unknown_error';
        $errorMessage = is_array($data) ? ($data['errorMessage'] ?? 'An error occurred') : 'An error occurred';
        
        Log::warning('Teller enrollment error', [
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'user_id' => $this->user->id,
        ]);

        $this->error = 'Bank linking error: ' . $errorMessage;
        $this->showConnect = false;
        $this->isLinking = false;
    }

    /**
     * Fetch and cache institution data from Teller
     * 
     * NOTE: This method is disabled in development mode
     * Institution API may return 404 or require different authentication
     * Phase 4 will implement fallback strategy (hardcoded URLs or external API)
     *
     * @param string $institutionId
     * @return void
     */
    private function fetchAndCacheInstitution(string $institutionId): void
    {
        // DISABLED - See note above
        Log::info('Institution caching disabled - will implement fallback in Phase 4', [
                'institution_id' => $institutionId,
            ]);
    }

    /**
     * Close the Teller Connect widget
     */
    public function closeConnect(): void
    {
        $this->showConnect = false;
        $this->isLinking = false;
    }

    public function render()
    {
        return view('livewire.accounts.teller-connect');
    }
}

