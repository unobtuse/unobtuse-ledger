<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncAccountTransactions;
use App\Jobs\SyncAccountLiabilities;
use App\Models\Account;
use App\Services\PlaidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Account Controller
 * 
 * Handles bank account linking via Plaid, account management,
 * and balance updates.
 */
class AccountController extends Controller
{
    public function __construct(
        protected PlaidService $plaidService
    ) {}

    /**
     * Display account management page.
     *
     * @return View
     */
    public function index(): View
    {
        $accounts = auth()->user()->accounts()
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('accounts.index', compact('accounts'));
    }

    /**
     * Create a Plaid Link token for account linking.
     *
     * @return JsonResponse
     */
    public function createLinkToken(): JsonResponse
    {
        try {
            $user = auth()->user();
            $linkTokenResponse = $this->plaidService->createLinkToken($user);

            return response()->json([
                'link_token' => $linkTokenResponse['link_token'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create Plaid link token', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to initialize account linking.',
            ], 500);
        }
    }

    /**
     * Exchange public token and create account records.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exchangePublicToken(Request $request): JsonResponse
    {
        $request->validate([
            'public_token' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $user = auth()->user();
            
            // Exchange public token for access token
            $tokenData = $this->plaidService->exchangePublicToken($request->public_token);
            $accessToken = $tokenData['access_token'];
            $itemId = $tokenData['item_id'];

            // Get accounts from Plaid
            $accountsData = $this->plaidService->getAccounts($accessToken);
            $plaidAccounts = $accountsData['accounts'];
            $item = $accountsData['item'];

            // Get institution details
            $institutionId = $item['institution_id'] ?? null;
            $institution = $institutionId 
                ? $this->plaidService->getInstitution($institutionId)
                : null;

            $createdAccounts = [];

            // Create account records
            foreach ($plaidAccounts as $plaidAccount) {
                $account = Account::create([
                    'user_id' => $user->id,
                    'plaid_account_id' => $plaidAccount['account_id'],
                    'plaid_access_token' => $accessToken, // Will be encrypted
                    'plaid_item_id' => $itemId,
                    'account_name' => $plaidAccount['name'],
                    'official_name' => $plaidAccount['official_name'] ?? null,
                    'account_type' => $this->mapAccountType($plaidAccount['type']),
                    'account_subtype' => $plaidAccount['subtype'] ?? null,
                    'institution_id' => $institutionId,
                    'institution_name' => $institution['name'] ?? 'Unknown',
                    'balance' => $plaidAccount['balances']['current'] ?? 0,
                    'available_balance' => $plaidAccount['balances']['available'] ?? null,
                    'credit_limit' => $plaidAccount['balances']['limit'] ?? null,
                    'currency' => $plaidAccount['balances']['iso_currency_code'] ?? 'USD',
                    'mask' => $plaidAccount['mask'] ?? null,
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                    'is_active' => true,
                    'metadata' => $plaidAccount,
                ]);

                // Dispatch transaction sync job
                SyncAccountTransactions::dispatch($account);

                // Dispatch liabilities sync job for eligible account types
                if (in_array($account->account_type, ['credit_card', 'loan'])) {
                    SyncAccountLiabilities::dispatch($account);
                }

                $createdAccounts[] = $account;
            }

            return response()->json([
                'success' => true,
                'message' => 'Account(s) linked successfully!',
                'accounts' => $createdAccounts,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to exchange public token', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to link account. Please try again.',
            ], 500);
        }
    }

    /**
     * Refresh account balance.
     *
     * @param Account $account
     * @return JsonResponse
     */
    public function refreshBalance(Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        try {
            $balances = $this->plaidService->getBalance($account->plaid_access_token);

            foreach ($balances as $balance) {
                if ($balance['account_id'] === $account->plaid_account_id) {
                    $account->update([
                        'balance' => $balance['balances']['current'] ?? 0,
                        'available_balance' => $balance['balances']['available'] ?? null,
                        'credit_limit' => $balance['balances']['limit'] ?? null,
                        'last_synced_at' => now(),
                        'sync_status' => 'synced',
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Balance updated successfully!',
                        'balance' => $account->formatted_balance,
                    ]);
                }
            }

            return response()->json([
                'error' => 'Account not found.',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to refresh balance', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to refresh balance.',
            ], 500);
        }
    }

    /**
     * Disconnect account.
     *
     * @param Account $account
     * @return JsonResponse|RedirectResponse
     */
    public function disconnect(Account $account)
    {
        $this->authorize('delete', $account);

        try {
            // Remove from Plaid
            $this->plaidService->removeItem($account->plaid_access_token);

            // Soft delete the account
            $account->update(['is_active' => false]);
            $account->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Account disconnected successfully.',
                ]);
            }

            return redirect()->route('accounts.index')
                ->with('success', 'Account disconnected successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to disconnect account', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to disconnect account.',
                ], 500);
            }

            return redirect()->route('accounts.index')
                ->with('error', 'Failed to disconnect account.');
        }
    }

    /**
     * Trigger manual sync for an account.
     *
     * @param Account $account
     * @return JsonResponse|RedirectResponse
     */
    public function sync(Account $account)
    {
        $this->authorize('update', $account);

        try {
            SyncAccountTransactions::dispatch($account);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction sync started. This may take a few moments.',
                ]);
            }

            return redirect()->back()
                ->with('success', 'Transaction sync started. This may take a few moments.');

        } catch (\Exception $e) {
            Log::error('Failed to trigger sync', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to start sync.',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to start sync.');
        }
    }

    /**
     * Update account nickname.
     *
     * @param Request $request
     * @param Account $account
     * @return JsonResponse
     */
    public function updateNickname(Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        $request->validate([
            'nickname' => 'nullable|string|max:255',
        ]);

        try {
            $account->update([
                'nickname' => $request->nickname ?: null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nickname updated successfully!',
                'display_name' => $account->display_name_without_mask,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update nickname', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update nickname.',
            ], 500);
        }
    }

    /**
     * Handle Plaid OAuth callback.
     * This is called when Plaid redirects back after OAuth authentication.
     * We render the accounts page so the JavaScript can detect OAuth state and complete the flow.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function oauthCallback(Request $request)
    {
        try {
            // Get OAuth state and code from query parameters
            $oauthStateId = $request->query('oauth_state_id');
            $error = $request->query('error');
            
            if ($error) {
                Log::error('Plaid OAuth callback error', [
                    'error' => $error,
                    'user_id' => auth()->id(),
                ]);
                
                return redirect()->route('accounts.index')
                    ->with('error', 'Account linking failed. Please try again.');
            }
            
            // Render the accounts page - the JavaScript will detect oauth_state_id
            // and automatically reinitialize Plaid Link to complete the OAuth flow
            $accounts = auth()->user()->accounts()
                ->orderBy('is_active', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return view('accounts.index', compact('accounts'));
                
        } catch (\Exception $e) {
            Log::error('Plaid OAuth callback failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('accounts.index')
                ->with('error', 'Failed to process OAuth callback.');
        }
    }

    /**
     * Map Plaid account type to our types.
     *
     * @param string $plaidType
     * @return string
     */
    protected function mapAccountType(string $plaidType): string
    {
        return match($plaidType) {
            'depository' => 'checking', // Will be refined by subtype
            'credit' => 'credit_card',
            'loan' => 'loan',
            'investment' => 'investment',
            default => 'other',
        };
    }
}
