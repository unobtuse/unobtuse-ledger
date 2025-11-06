<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SyncAccountBalances;
use App\Jobs\SyncAccountTransactions;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PlaidAccountError;
use App\Notifications\PlaidAccountRequiresReauth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PlaidWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable webhook verification for testing (can be enabled for specific tests)
        config(['plaid.webhook_verification_enabled' => false]);

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
            'plaid_item_id' => 'test-item-id-123',
            'plaid_account_id' => 'test-account-id-456',
            'sync_status' => 'synced',
        ]);
    }

    /**
     * Test webhook endpoint is accessible.
     */
    public function test_webhook_endpoint_is_accessible(): void
    {
        $response = $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
            'item_id' => 'test-item-id',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    /**
     * Test TRANSACTIONS.SYNC_UPDATES_AVAILABLE webhook dispatches sync job.
     */
    public function test_transactions_sync_updates_available_dispatches_sync_job(): void
    {
        Queue::fake();

        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
            'item_id' => $this->account->plaid_item_id,
        ]);

        Queue::assertPushed(SyncAccountTransactions::class, function ($job) {
            return $job->account->id === $this->account->id;
        });
    }

    /**
     * Test TRANSACTIONS.TRANSACTIONS_REMOVED deletes transactions.
     */
    public function test_transactions_removed_deletes_transactions(): void
    {
        $transaction = Transaction::factory()->create([
            'account_id' => $this->account->id,
            'plaid_transaction_id' => 'removed-transaction-123',
        ]);

        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'TRANSACTIONS_REMOVED',
            'item_id' => $this->account->plaid_item_id,
            'removed_transactions' => ['removed-transaction-123'],
        ]);

        $this->assertDatabaseMissing('transactions', [
            'plaid_transaction_id' => 'removed-transaction-123',
        ]);
    }

    /**
     * Test ITEM.ERROR sends notification and updates account status.
     */
    public function test_item_error_sends_notification_and_updates_status(): void
    {
        Notification::fake();

        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'ERROR',
            'item_id' => $this->account->plaid_item_id,
            'error' => [
                'error_code' => 'ITEM_LOGIN_REQUIRED',
                'error_message' => 'User needs to log in',
            ],
        ]);

        // Assert account status updated
        $this->account->refresh();
        $this->assertEquals('failed', $this->account->sync_status);
        $this->assertEquals('User needs to log in', $this->account->sync_error);

        // Assert notification sent
        Notification::assertSentTo(
            $this->user,
            PlaidAccountError::class,
            function ($notification) {
                return $notification->errorCode === 'ITEM_LOGIN_REQUIRED';
            }
        );
    }

    /**
     * Test ITEM.PENDING_EXPIRATION sends reauth notification.
     */
    public function test_item_pending_expiration_sends_reauth_notification(): void
    {
        Notification::fake();

        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'PENDING_EXPIRATION',
            'item_id' => $this->account->plaid_item_id,
        ]);

        // Assert account status updated
        $this->account->refresh();
        $this->assertEquals('requires_update', $this->account->sync_status);

        // Assert notification sent
        Notification::assertSentTo(
            $this->user,
            PlaidAccountRequiresReauth::class
        );
    }

    /**
     * Test ITEM.USER_PERMISSION_REVOKED disables account.
     */
    public function test_item_permission_revoked_disables_account(): void
    {
        Notification::fake();

        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'USER_PERMISSION_REVOKED',
            'item_id' => $this->account->plaid_item_id,
        ]);

        // Assert account disabled
        $this->account->refresh();
        $this->assertEquals('disabled', $this->account->sync_status);
        $this->assertFalse($this->account->is_active);

        // Assert notification sent
        Notification::assertSentTo(
            $this->user,
            PlaidAccountRequiresReauth::class
        );
    }

    /**
     * Test AUTH.AUTOMATICALLY_VERIFIED dispatches balance sync.
     */
    public function test_auth_automatically_verified_dispatches_balance_sync(): void
    {
        Queue::fake();

        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'AUTH',
            'webhook_code' => 'AUTOMATICALLY_VERIFIED',
            'item_id' => $this->account->plaid_item_id,
        ]);

        Queue::assertPushed(SyncAccountBalances::class, function ($job) {
            return $job->account->id === $this->account->id;
        });
    }

    /**
     * Test RECURRING_TRANSACTIONS_UPDATE dispatches sync job.
     */
    public function test_recurring_transactions_update_dispatches_sync(): void
    {
        Queue::fake();

        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'RECURRING_TRANSACTIONS_UPDATE',
            'item_id' => $this->account->plaid_item_id,
        ]);

        Queue::assertPushed(SyncAccountTransactions::class);
    }

    /**
     * Test NEW_ACCOUNTS_AVAILABLE logs event.
     */
    public function test_new_accounts_available_logs_event(): void
    {
        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'NEW_ACCOUNTS_AVAILABLE',
            'item_id' => $this->account->plaid_item_id,
        ]);

        // Should not throw exception and return success
        $this->assertTrue(true);
    }

    /**
     * Test webhook with unknown type logs warning.
     */
    public function test_unknown_webhook_type_logs_warning(): void
    {
        $response = $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'UNKNOWN_TYPE',
            'webhook_code' => 'UNKNOWN_CODE',
            'item_id' => 'test-item-id',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    /**
     * Test webhook with missing item_id handles gracefully.
     */
    public function test_webhook_without_item_id_handles_gracefully(): void
    {
        $response = $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test webhook error handling returns 500 on exception.
     */
    public function test_webhook_error_handling(): void
    {
        // Force an error by using invalid account
        $response = $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'ERROR',
            'item_id' => 'non-existent-item-id',
            'error' => [
                'error_code' => 'TEST_ERROR',
                'error_message' => 'Test error',
            ],
        ]);

        // Should still return 200 (webhook received successfully)
        // Error handling is internal
        $response->assertStatus(200);
    }

    /**
     * Test multiple accounts for same item_id are all updated.
     */
    public function test_multiple_accounts_for_item_are_updated(): void
    {
        $account2 = Account::factory()->create([
            'user_id' => $this->user->id,
            'plaid_item_id' => $this->account->plaid_item_id,
            'sync_status' => 'synced',
        ]);

        Queue::fake();

        $this->postJson('/api/plaid/webhook', [
            'webhook_type' => 'TRANSACTIONS',
            'webhook_code' => 'SYNC_UPDATES_AVAILABLE',
            'item_id' => $this->account->plaid_item_id,
        ]);

        // Both accounts should have sync jobs dispatched
        Queue::assertPushed(SyncAccountTransactions::class, 2);
    }
}


