<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncAccountTransactions;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Plaid Webhook Controller
 * 
 * Handles webhook notifications from Plaid for account updates,
 * transaction changes, and other events.
 */
class PlaidWebhookController extends Controller
{
    /**
     * Handle Plaid webhook events.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        // Log incoming webhook
        Log::info('Plaid webhook received', [
            'webhook_type' => $request->input('webhook_type'),
            'webhook_code' => $request->input('webhook_code'),
            'item_id' => $request->input('item_id'),
        ]);

        $webhookType = $request->input('webhook_type');
        $webhookCode = $request->input('webhook_code');
        $itemId = $request->input('item_id');

        try {
            // Handle different webhook types
            match($webhookType) {
                'TRANSACTIONS' => $this->handleTransactionsWebhook($webhookCode, $itemId, $request),
                'ITEM' => $this->handleItemWebhook($webhookCode, $itemId, $request),
                'AUTH' => $this->handleAuthWebhook($webhookCode, $itemId, $request),
                'ASSETS' => $this->handleAssetsWebhook($webhookCode, $itemId, $request),
                'HOLDINGS' => $this->handleHoldingsWebhook($webhookCode, $itemId, $request),
                'INCOME' => $this->handleIncomeWebhook($webhookCode, $itemId, $request),
                'LIABILITIES' => $this->handleLiabilitiesWebhook($webhookCode, $itemId, $request),
                default => Log::warning('Unknown webhook type', ['type' => $webhookType]),
            };

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error processing Plaid webhook', [
                'error' => $e->getMessage(),
                'webhook_type' => $webhookType,
                'webhook_code' => $webhookCode,
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle TRANSACTIONS webhook events.
     *
     * @param string $code
     * @param string $itemId
     * @param Request $request
     * @return void
     */
    protected function handleTransactionsWebhook(string $code, string $itemId, Request $request): void
    {
        match($code) {
            'SYNC_UPDATES_AVAILABLE' => $this->syncTransactionsForItem($itemId),
            'INITIAL_UPDATE' => $this->syncTransactionsForItem($itemId),
            'HISTORICAL_UPDATE' => $this->syncTransactionsForItem($itemId),
            'DEFAULT_UPDATE' => $this->syncTransactionsForItem($itemId),
            'TRANSACTIONS_REMOVED' => $this->handleTransactionsRemoved($request),
            default => Log::info('Unhandled TRANSACTIONS webhook code', ['code' => $code]),
        };
    }

    /**
     * Handle ITEM webhook events.
     *
     * @param string $code
     * @param string $itemId
     * @param Request $request
     * @return void
     */
    protected function handleItemWebhook(string $code, string $itemId, Request $request): void
    {
        match($code) {
            'ERROR' => $this->handleItemError($itemId, $request),
            'PENDING_EXPIRATION' => $this->handlePendingExpiration($itemId),
            'USER_PERMISSION_REVOKED' => $this->handlePermissionRevoked($itemId),
            'WEBHOOK_UPDATE_ACKNOWLEDGED' => Log::info('Webhook acknowledged', ['item_id' => $itemId]),
            default => Log::info('Unhandled ITEM webhook code', ['code' => $code]),
        };
    }

    /**
     * Handle AUTH webhook events (account balance updates).
     *
     * @param string $code
     * @param string $itemId
     * @param Request $request
     * @return void
     */
    protected function handleAuthWebhook(string $code, string $itemId, Request $request): void
    {
        match($code) {
            'AUTOMATICALLY_VERIFIED' => Log::info('Auth automatically verified', ['item_id' => $itemId]),
            'VERIFICATION_EXPIRED' => Log::warning('Auth verification expired', ['item_id' => $itemId]),
            default => Log::info('Unhandled AUTH webhook code', ['code' => $code]),
        };
    }

    /**
     * Handle ASSETS webhook events.
     *
     * @param string $code
     * @param string $itemId
     * @param Request $request
     * @return void
     */
    protected function handleAssetsWebhook(string $code, string $itemId, Request $request): void
    {
        Log::info('ASSETS webhook received', ['code' => $code, 'item_id' => $itemId]);
    }

    /**
     * Handle HOLDINGS webhook events.
     *
     * @param string $code
     * @param string $itemId
     * @param Request $request
     * @return void
     */
    protected function handleHoldingsWebhook(string $code, string $itemId, Request $request): void
    {
        Log::info('HOLDINGS webhook received', ['code' => $code, 'item_id' => $itemId]);
    }

    /**
     * Handle INCOME webhook events.
     *
     * @param string $code
     * @param string $itemId
     * @param Request $request
     * @return void
     */
    protected function handleIncomeWebhook(string $code, string $itemId, Request $request): void
    {
        Log::info('INCOME webhook received', ['code' => $code, 'item_id' => $itemId]);
    }

    /**
     * Handle LIABILITIES webhook events.
     *
     * @param string $code
     * @param string $itemId
     * @param Request $request
     * @return void
     */
    protected function handleLiabilitiesWebhook(string $code, string $itemId, Request $request): void
    {
        Log::info('LIABILITIES webhook received', ['code' => $code, 'item_id' => $itemId]);
    }

    /**
     * Sync transactions for all accounts in an item.
     *
     * @param string $itemId
     * @return void
     */
    protected function syncTransactionsForItem(string $itemId): void
    {
        $accounts = Account::where('plaid_item_id', $itemId)->get();

        foreach ($accounts as $account) {
            SyncAccountTransactions::dispatch($account);
        }

        Log::info('Dispatched transaction sync jobs', [
            'item_id' => $itemId,
            'accounts_count' => $accounts->count(),
        ]);
    }

    /**
     * Handle removed transactions.
     *
     * @param Request $request
     * @return void
     */
    protected function handleTransactionsRemoved(Request $request): void
    {
        $removedTransactions = $request->input('removed_transactions', []);

        foreach ($removedTransactions as $transactionId) {
            \App\Models\Transaction::where('plaid_transaction_id', $transactionId)
                ->delete();
        }

        Log::info('Removed transactions', ['count' => count($removedTransactions)]);
    }

    /**
     * Handle item errors.
     *
     * @param string $itemId
     * @param Request $request
     * @return void
     */
    protected function handleItemError(string $itemId, Request $request): void
    {
        $error = $request->input('error');

        Account::where('plaid_item_id', $itemId)
            ->update([
                'sync_status' => 'failed',
                'sync_error' => $error['error_message'] ?? 'Unknown error',
            ]);

        Log::error('Plaid item error', [
            'item_id' => $itemId,
            'error_code' => $error['error_code'] ?? null,
            'error_message' => $error['error_message'] ?? null,
        ]);
    }

    /**
     * Handle pending expiration (user needs to re-authenticate).
     *
     * @param string $itemId
     * @return void
     */
    protected function handlePendingExpiration(string $itemId): void
    {
        Account::where('plaid_item_id', $itemId)
            ->update(['sync_status' => 'requires_update']);

        Log::warning('Plaid item pending expiration', ['item_id' => $itemId]);

        // TODO: Notify user to re-authenticate
    }

    /**
     * Handle user permission revoked.
     *
     * @param string $itemId
     * @return void
     */
    protected function handlePermissionRevoked(string $itemId): void
    {
        Account::where('plaid_item_id', $itemId)
            ->update([
                'sync_status' => 'disabled',
                'is_active' => false,
            ]);

        Log::warning('User revoked Plaid permissions', ['item_id' => $itemId]);
    }
}


