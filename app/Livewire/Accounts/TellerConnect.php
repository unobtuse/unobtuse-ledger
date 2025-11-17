<?php

declare(strict_types=1);

namespace App\Livewire\Accounts;

use App\Models\Account;
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

    protected TellerService $tellerService;

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->tellerService = app(TellerService::class);
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
            $config = $this->tellerService->initializeTellerConnect($this->user);

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
     * @param string $enrollmentToken
     */
    #[\Livewire\Attributes\On('tellerEnrollmentSuccess')]
    public function handleEnrollmentSuccess(string $enrollmentToken): void
    {
        try {
            $this->isLinking = true;

            Log::info('Teller enrollment received', [
                'user_id' => $this->user->id,
                'token' => substr($enrollmentToken, 0, 10),
            ]);

            // Exchange enrollment token for access token
            $result = $this->tellerService->exchangeToken($enrollmentToken);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to exchange token');
            }

            $accessToken = $result['accessToken'];
            $tellerAccountId = $result['accountId'];

            // Get account details from Teller
            $accountDetails = $this->tellerService->getAccountDetails($accessToken, $tellerAccountId);

            if (!$accountDetails['success']) {
                throw new \Exception('Failed to retrieve account details');
            }

            $account = $accountDetails['account'];

            // Create/update account record in our database
            $dbAccount = Account::create([
                'user_id' => $this->user->id,
                'account_name' => $account['name'] ?? 'Bank Account',
                'account_type' => $account['subtype'] ?? $account['type'] ?? 'checking',
                'account_number' => $account['account_number'] ?? 'N/A',
                'institution_name' => $account['institution'] ?? 'Teller',
                'teller_token' => $accessToken,
                'teller_account_id' => $tellerAccountId,
                'balance' => 0, // Will be synced in job
                'is_active' => true,
            ]);

            Log::info('Account created from Teller', [
                'user_id' => $this->user->id,
                'account_id' => $dbAccount->id,
                'teller_account_id' => $tellerAccountId,
            ]);

            // Sync initial balance
            $balanceResult = $this->tellerService->getBalance($accessToken, $tellerAccountId);

            if ($balanceResult['success'] && isset($balanceResult['balance']['available'])) {
                $dbAccount->update(['balance' => $balanceResult['balance']['available']]);
            }

            // Dispatch job to sync initial transactions
            \App\Jobs\SyncTellerTransactions::dispatch($dbAccount);

            $this->showConnect = false;
            $this->isLinking = false;
            $this->success = 'Bank account linked successfully!';

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
     *
     * @param string $errorCode
     * @param string $errorMessage
     */
    #[\Livewire\Attributes\On('tellerEnrollmentError')]
    public function handleEnrollmentError(string $errorCode, string $errorMessage): void
    {
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

