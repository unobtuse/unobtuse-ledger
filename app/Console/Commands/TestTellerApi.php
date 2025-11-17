<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\TellerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Test Teller API Endpoints
 *
 * This command calls each Teller API endpoint with a test account
 * and logs the full responses for debugging/analysis.
 *
 * Usage: php artisan teller:test-api
 */
class TestTellerApi extends Command
{
    protected $signature = 'teller:test-api';
    protected $description = 'Test Teller API endpoints and log full responses';

    public function handle(TellerService $tellerService): int
    {
        $this->line('Testing Teller API Endpoints');
        $this->line('=============================');

        // Get first Teller account
        $account = Account::whereNotNull('teller_token')
            ->whereNotNull('teller_account_id')
            ->first();

        if (!$account) {
            $this->error('No Teller accounts found. Please link an account first.');
            return 1;
        }

        $this->info("Testing with account: {$account->account_name} ({$account->teller_account_id})");
        $this->line('');

        // Test Balances endpoint
        $this->testBalance($tellerService, $account);
        $this->line('');

        // Test Transactions endpoint
        $this->testTransactions($tellerService, $account);
        $this->line('');

        // Test Account Details endpoint
        $this->testAccountDetails($tellerService, $account);
        $this->line('');

        // Test Institution endpoint (if we have institution_id)
        if ($account->institution_id) {
            $this->testInstitution($tellerService, $account);
        }

        $this->info('✓ All tests completed. Check logs/teller.log for full responses.');
        $this->info('  Also check logs/laravel.log for structured data analysis.');

        return 0;
    }

    private function testBalance(TellerService $tellerService, Account $account): void
    {
        $this->line('Testing GET /balances');
        $this->line('---------------------');

        try {
            $result = $tellerService->getBalance($account->teller_token, $account->teller_account_id);

            if ($result['success']) {
                $balance = $result['balance'];
                $this->info('✓ Balance call succeeded');
                $this->line("  Fields available: " . implode(', ', array_keys((array)$balance)));

                // Show values
                foreach ($balance as $key => $value) {
                    $value = is_array($value) ? json_encode($value) : $value;
                    $this->line("  - $key: $value");
                }
            } else {
                $this->error('✗ Balance call failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error('✗ Balance test error: ' . $e->getMessage());
        }
    }

    private function testTransactions(TellerService $tellerService, Account $account): void
    {
        $this->line('Testing GET /transactions');
        $this->line('-------------------------');

        try {
            $result = $tellerService->getTransactions($account->teller_token, $account->teller_account_id, [
                'count' => 5,
            ]);

            if ($result['success']) {
                $transactions = $result['transactions'];
                $this->info("✓ Transactions call succeeded (" . count($transactions) . " found)");

                if (count($transactions) > 0) {
                    $this->line("  Sample transaction fields: " . implode(', ', array_keys($transactions[0])));
                    $this->line("  First transaction:");
                    foreach ($transactions[0] as $key => $value) {
                        $value = is_array($value) ? json_encode($value) : $value;
                        $this->line("    - $key: $value");
                    }
                } else {
                    $this->warn('  No transactions found');
                }
            } else {
                $this->error('✗ Transactions call failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error('✗ Transactions test error: ' . $e->getMessage());
        }
    }

    private function testAccountDetails(TellerService $tellerService, Account $account): void
    {
        $this->line('Testing GET /accounts/{id}');
        $this->line('---------------------------');

        try {
            $result = $tellerService->getAccountDetails($account->teller_token, $account->teller_account_id);

            if ($result['success']) {
                $details = $result['account'];
                $this->info('✓ Account details call succeeded');
                $this->line("  Fields available: " . implode(', ', array_keys((array)$details)));

                // Show relevant fields
                foreach ($details as $key => $value) {
                    if (!is_array($value) && !is_object($value)) {
                        $this->line("  - $key: $value");
                    }
                }
            } else {
                $this->error('✗ Account details call failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error('✗ Account details test error: ' . $e->getMessage());
        }
    }

    private function testInstitution(TellerService $tellerService, Account $account): void
    {
        $this->line('Testing GET /institutions/{id}');
        $this->line('--------------------------------');

        try {
            $result = $tellerService->getInstitution($account->institution_id);

            if ($result['success']) {
                $institution = $result['institution'];
                $this->info('✓ Institution call succeeded');
                $this->line("  Fields available: " . implode(', ', array_keys((array)$institution)));

                // Show relevant fields
                foreach ($institution as $key => $value) {
                    if (!is_array($value) && !is_object($value)) {
                        $this->line("  - $key: $value");
                    }
                }
            } else {
                $this->error('✗ Institution call failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error('✗ Institution test error: ' . $e->getMessage());
        }
    }
}

