<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\TellerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Teller Webhook Controller
 *
 * Handles incoming webhook events from Teller API.
 * Events include: account.disconnected, transaction.created, payment.completed, etc.
 */
class TellerWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Teller
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        try {
            // Log incoming webhook
            $payload = $request->all();
            $eventType = $payload['event'] ?? 'unknown';
            
            Log::info('Teller webhook received', [
                'event_type' => $eventType,
                'payload_keys' => array_keys($payload),
            ]);

            // Verify webhook signature (if Teller provides one)
            // Note: Teller may use HMAC signature verification
            // For now, we'll log and process - add signature verification later if needed
            if (!$this->verifySignature($request)) {
                Log::warning('Teller webhook signature verification failed', [
                    'event_type' => $eventType,
                ]);
                // Continue processing but log the warning
            }

            // Store webhook event for logging/auditing
            $webhookEvent = WebhookEvent::create([
                'id' => (string) Str::uuid(),
                'event_type' => $eventType,
                'teller_event_id' => $payload['id'] ?? null,
                'payload' => $payload,
                'account_id' => $payload['data']['account_id'] ?? null,
                'transaction_id' => $payload['data']['transaction_id'] ?? null,
                'status' => 'pending',
            ]);

            // Process the webhook event
            $this->processWebhookEvent($webhookEvent, $payload);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Teller webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Error processing webhook', 500);
        }
    }

    /**
     * Verify webhook signature (if Teller provides signature verification)
     *
     * @param Request $request
     * @return bool
     */
    private function verifySignature(Request $request): bool
    {
        // Teller may provide signature in headers
        // For now, return true - implement actual verification when Teller docs specify
        $signature = $request->header('X-Teller-Signature');
        
        if (!$signature) {
            // No signature provided - may be optional for development
            return true;
        }

        // TODO: Implement HMAC signature verification when Teller provides details
        // $expectedSignature = hash_hmac('sha256', $request->getContent(), config('teller.webhook_secret'));
        // return hash_equals($expectedSignature, $signature);

        return true;
    }

    /**
     * Process webhook event based on event type
     *
     * @param WebhookEvent $webhookEvent
     * @param array $payload
     * @return void
     */
    private function processWebhookEvent(WebhookEvent $webhookEvent, array $payload): void
    {
        try {
            $eventType = $webhookEvent->event_type;
            $data = $payload['data'] ?? [];

            switch ($eventType) {
                case 'account.disconnected':
                    $this->handleAccountDisconnected($data);
                    break;

                case 'account.updated':
                    $this->handleAccountUpdated($data);
                    break;

                case 'transaction.created':
                case 'transaction.updated':
                    $this->handleTransactionEvent($eventType, $data);
                    break;

                case 'payment.completed':
                case 'payment.failed':
                    $this->handlePaymentEvent($eventType, $data);
                    break;

                default:
                    Log::info('Unhandled webhook event type', [
                        'event_type' => $eventType,
                        'webhook_id' => $webhookEvent->id,
                    ]);
            }

            // Mark as processed
            $webhookEvent->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook event processing failed', [
                'webhook_id' => $webhookEvent->id,
                'event_type' => $webhookEvent->event_type,
                'error' => $e->getMessage(),
            ]);

            $webhookEvent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle account.disconnected event
     *
     * @param array $data
     * @return void
     */
    private function handleAccountDisconnected(array $data): void
    {
        $accountId = $data['account_id'] ?? null;
        
        if (!$accountId) {
            Log::warning('account.disconnected event missing account_id', ['data' => $data]);
            return;
        }

        // Find account by Teller account ID
        $account = Account::where('teller_account_id', $accountId)->first();

        if ($account) {
            $account->update([
                'is_active' => false,
                'sync_status' => 'disabled',
            ]);

            Log::info('Account deactivated via webhook', [
                'account_id' => $account->id,
                'teller_account_id' => $accountId,
            ]);
        } else {
            Log::warning('Account not found for disconnect webhook', [
                'teller_account_id' => $accountId,
            ]);
        }
    }

    /**
     * Handle account.updated event
     *
     * @param array $data
     * @return void
     */
    private function handleAccountUpdated(array $data): void
    {
        $accountId = $data['account_id'] ?? null;
        
        if (!$accountId) {
            return;
        }

        $account = Account::where('teller_account_id', $accountId)->first();

        if ($account && $account->teller_token) {
            // Refresh account details from Teller
            $tellerService = app(TellerService::class);
            $accountDetails = $tellerService->getAccountDetails($account->teller_token, $accountId);

            if ($accountDetails['success']) {
                $tellerAccount = $accountDetails['account'];
                
                $account->update([
                    'account_name' => $tellerAccount['name'] ?? $account->account_name,
                    'account_type' => strtolower($tellerAccount['subtype'] ?? $tellerAccount['type'] ?? $account->account_type),
                ]);

                Log::info('Account updated via webhook', [
                    'account_id' => $account->id,
                ]);
            }
        }
    }

    /**
     * Handle transaction.created or transaction.updated events
     *
     * @param string $eventType
     * @param array $data
     * @return void
     */
    private function handleTransactionEvent(string $eventType, array $data): void
    {
        $accountId = $data['account_id'] ?? null;
        $transactionId = $data['transaction_id'] ?? null;

        if (!$accountId || !$transactionId) {
            return;
        }

        $account = Account::where('teller_account_id', $accountId)->first();

        if ($account && $account->teller_token) {
            // Dispatch job to sync transactions (will handle duplicates)
            \App\Jobs\SyncTellerTransactions::dispatch($account);

            Log::info('Transaction sync triggered via webhook', [
                'event_type' => $eventType,
                'account_id' => $account->id,
                'transaction_id' => $transactionId,
            ]);
        }
    }

    /**
     * Handle payment.completed or payment.failed events
     *
     * @param string $eventType
     * @param array $data
     * @return void
     */
    private function handlePaymentEvent(string $eventType, array $data): void
    {
        $paymentId = $data['payment_id'] ?? null;
        $accountId = $data['account_id'] ?? null;

        if (!$paymentId || !$accountId) {
            Log::warning('Payment webhook missing required data', [
                'event_type' => $eventType,
                'data' => $data,
            ]);
            return;
        }

        // Find payment by Teller payment ID
        $payment = Payment::where('teller_payment_id', $paymentId)->first();

        if ($payment) {
            $newStatus = $eventType === 'payment.completed' ? 'completed' : 'failed';
            
            $updateData = [
                'status' => $newStatus,
                'processed_date' => now(),
            ];

            if (isset($data['status_message'])) {
                $updateData['status_message'] = $data['status_message'];
            }

            $payment->update($updateData);

            Log::info('Payment status updated via webhook', [
                'payment_id' => $payment->id,
                'teller_payment_id' => $paymentId,
                'new_status' => $newStatus,
            ]);
        } else {
            Log::warning('Payment not found for webhook', [
                'teller_payment_id' => $paymentId,
            ]);
        }
    }
}
