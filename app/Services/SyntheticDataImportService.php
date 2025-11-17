<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\PaySchedule;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Synthetic Data Import Service
 * 
 * Validates and imports synthetic financial data from JSON format.
 * Handles account references, bill references, and data integrity checks.
 */
class SyntheticDataImportService
{
    private array $errors = [];
    private array $warnings = [];
    private array $accountMap = []; // Maps account indices to UUIDs
    private array $billMap = []; // Maps bill indices to UUIDs
    private User $user;
    private array $data;

    /**
     * Validate JSON structure and data integrity.
     *
     * @param array $data
     * @param User $user
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validate(array $data, User $user): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->user = $user;
        $this->data = $data;

        // Validate root structure
        $this->validateRootStructure();

        // Validate each section
        $this->validateDemoUser();
        $this->validateAccounts();
        $this->validatePaySchedules();
        $this->validateBills();
        $this->validateTransactions();
        $this->validateBudgets();

        // Cross-reference validation
        $this->validateAccountReferences();
        $this->validateBillReferences();
        $this->validateDateRanges();
        $this->validateAmountConsistency();

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'summary' => $this->getSummary(),
        ];
    }

    /**
     * Import validated data into database.
     *
     * @param array $data
     * @param User $user
     * @return array ['success' => bool, 'imported' => array, 'errors' => array]
     */
    public function import(array $data, User $user): array
    {
        $this->user = $user;
        $this->data = $data;
        $this->accountMap = [];
        $this->billMap = [];

        $imported = [
            'accounts' => 0,
            'transactions' => 0,
            'bills' => 0,
            'pay_schedules' => 0,
            'budgets' => 0,
        ];

        try {
            DB::beginTransaction();

            // Import in order: accounts -> pay schedules -> bills -> transactions -> budgets
            $imported['accounts'] = $this->importAccounts();
            $imported['pay_schedules'] = $this->importPaySchedules();
            $imported['bills'] = $this->importBills();
            $imported['transactions'] = $this->importTransactions();
            $imported['budgets'] = $this->importBudgets();

            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Synthetic data import failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'imported' => $imported,
                'errors' => ['Import failed: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Validate root structure.
     */
    private function validateRootStructure(): void
    {
        $required = ['demo_user', 'accounts', 'transactions', 'bills', 'pay_schedules', 'budgets'];

        foreach ($required as $key) {
            if (!isset($this->data[$key])) {
                $this->errors[] = "Missing required root key: {$key}";
            }
        }

        if (!is_array($this->data['accounts'] ?? null)) {
            $this->errors[] = "accounts must be an array";
        }
        if (!is_array($this->data['transactions'] ?? null)) {
            $this->errors[] = "transactions must be an array";
        }
        if (!is_array($this->data['bills'] ?? null)) {
            $this->errors[] = "bills must be an array";
        }
        if (!is_array($this->data['pay_schedules'] ?? null)) {
            $this->errors[] = "pay_schedules must be an array";
        }
        if (!is_array($this->data['budgets'] ?? null)) {
            $this->errors[] = "budgets must be an array";
        }
    }

    /**
     * Validate demo user object.
     */
    private function validateDemoUser(): void
    {
        if (!isset($this->data['demo_user'])) {
            return;
        }

        $demoUser = $this->data['demo_user'];

        if (!isset($demoUser['name']) || empty($demoUser['name'])) {
            $this->errors[] = "demo_user.name is required";
        }

        if (!isset($demoUser['email']) || empty($demoUser['email'])) {
            $this->errors[] = "demo_user.email is required";
        } elseif (!filter_var($demoUser['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "demo_user.email must be a valid email address";
        }
    }

    /**
     * Validate accounts array.
     */
    private function validateAccounts(): void
    {
        if (!isset($this->data['accounts']) || !is_array($this->data['accounts'])) {
            return;
        }

        $requiredFields = [
            'plaid_account_id',
            'plaid_access_token',
            'plaid_item_id',
            'account_name',
            'account_type',
            'institution_name',
        ];

        $accountTypes = ['checking', 'savings', 'credit_card', 'investment', 'loan', 'other'];
        $accountSubtypes = ['checking', 'savings', 'credit card', 'money market', 'cd', 'ira', '401k', 'student', 'mortgage', 'auto', 'other'];
        $syncStatuses = ['syncing', 'synced', 'failed', 'disabled'];

        foreach ($this->data['accounts'] as $index => $account) {
            // Check required fields
            foreach ($requiredFields as $field) {
                if (!isset($account[$field]) || $account[$field] === '') {
                    $this->errors[] = "accounts[{$index}].{$field} is required";
                }
            }

            // Validate enums
            if (isset($account['account_type']) && !in_array($account['account_type'], $accountTypes)) {
                $this->errors[] = "accounts[{$index}].account_type must be one of: " . implode(', ', $accountTypes);
            }

            if (isset($account['account_subtype']) && !in_array($account['account_subtype'], $accountSubtypes)) {
                $this->errors[] = "accounts[{$index}].account_subtype must be one of: " . implode(', ', $accountSubtypes);
            }

            if (isset($account['sync_status']) && !in_array($account['sync_status'], $syncStatuses)) {
                $this->errors[] = "accounts[{$index}].sync_status must be one of: " . implode(', ', $syncStatuses);
            }

            // Validate amounts
            if (isset($account['balance']) && !is_numeric($account['balance'])) {
                $this->errors[] = "accounts[{$index}].balance must be numeric";
            }

            if (isset($account['credit_limit']) && !is_numeric($account['credit_limit'])) {
                $this->errors[] = "accounts[{$index}].credit_limit must be numeric";
            }

            // Validate dates
            if (isset($account['last_synced_at']) && !$this->isValidDate($account['last_synced_at'])) {
                $this->errors[] = "accounts[{$index}].last_synced_at must be a valid ISO date";
            }

            if (isset($account['payment_due_date']) && !$this->isValidDate($account['payment_due_date'])) {
                $this->errors[] = "accounts[{$index}].payment_due_date must be a valid ISO date";
            }
        }
    }

    /**
     * Validate pay schedules array.
     */
    private function validatePaySchedules(): void
    {
        if (!isset($this->data['pay_schedules']) || !is_array($this->data['pay_schedules'])) {
            return;
        }

        $frequencies = ['weekly', 'biweekly', 'semimonthly', 'monthly', 'custom'];
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($this->data['pay_schedules'] as $index => $schedule) {
            if (!isset($schedule['frequency']) || !in_array($schedule['frequency'], $frequencies)) {
                $this->errors[] = "pay_schedules[{$index}].frequency is required and must be one of: " . implode(', ', $frequencies);
            }

            if (!isset($schedule['next_pay_date']) || !$this->isValidDate($schedule['next_pay_date'])) {
                $this->errors[] = "pay_schedules[{$index}].next_pay_date is required and must be a valid ISO date";
            }

            if (!isset($schedule['net_pay']) || !is_numeric($schedule['net_pay'])) {
                $this->errors[] = "pay_schedules[{$index}].net_pay is required and must be numeric";
            }

            if (isset($schedule['pay_day_of_week']) && !in_array($schedule['pay_day_of_week'], $daysOfWeek)) {
                $this->errors[] = "pay_schedules[{$index}].pay_day_of_week must be one of: " . implode(', ', $daysOfWeek);
            }

            if (isset($schedule['pay_day_of_month_1']) && ($schedule['pay_day_of_month_1'] < 1 || $schedule['pay_day_of_month_1'] > 31)) {
                $this->errors[] = "pay_schedules[{$index}].pay_day_of_month_1 must be between 1 and 31";
            }
        }
    }

    /**
     * Validate bills array.
     */
    private function validateBills(): void
    {
        if (!isset($this->data['bills']) || !is_array($this->data['bills'])) {
            return;
        }

        $frequencies = ['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'custom'];
        $categories = ['rent', 'mortgage', 'utilities', 'internet', 'phone', 'insurance', 'subscription', 'loan', 'credit_card', 'other'];
        $statuses = ['upcoming', 'due', 'overdue', 'paid', 'scheduled'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        foreach ($this->data['bills'] as $index => $bill) {
            $required = ['name', 'amount', 'due_date', 'next_due_date', 'frequency', 'category', 'payment_status'];
            foreach ($required as $field) {
                if (!isset($bill[$field])) {
                    $this->errors[] = "bills[{$index}].{$field} is required";
                }
            }

            if (isset($bill['frequency']) && !in_array($bill['frequency'], $frequencies)) {
                $this->errors[] = "bills[{$index}].frequency must be one of: " . implode(', ', $frequencies);
            }

            if (isset($bill['category']) && !in_array($bill['category'], $categories)) {
                $this->errors[] = "bills[{$index}].category must be one of: " . implode(', ', $categories);
            }

            if (isset($bill['payment_status']) && !in_array($bill['payment_status'], $statuses)) {
                $this->errors[] = "bills[{$index}].payment_status must be one of: " . implode(', ', $statuses);
            }

            if (isset($bill['priority']) && !in_array($bill['priority'], $priorities)) {
                $this->errors[] = "bills[{$index}].priority must be one of: " . implode(', ', $priorities);
            }

            if (isset($bill['amount']) && !is_numeric($bill['amount'])) {
                $this->errors[] = "bills[{$index}].amount must be numeric";
            }

            if (isset($bill['due_date']) && !$this->isValidDate($bill['due_date'])) {
                $this->errors[] = "bills[{$index}].due_date must be a valid ISO date";
            }

            if (isset($bill['next_due_date']) && !$this->isValidDate($bill['next_due_date'])) {
                $this->errors[] = "bills[{$index}].next_due_date must be a valid ISO date";
            }

            // Validate account reference
            if (isset($bill['account_id']) && !is_numeric($bill['account_id'])) {
                $this->errors[] = "bills[{$index}].account_id must be a numeric index";
            }
        }
    }

    /**
     * Validate transactions array.
     */
    private function validateTransactions(): void
    {
        if (!isset($this->data['transactions']) || !is_array($this->data['transactions'])) {
            return;
        }

        $required = ['account_id', 'name', 'amount', 'transaction_date', 'transaction_type'];
        $types = ['debit', 'credit', 'transfer'];
        $frequencies = ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'annual'];
        $channels = ['online', 'in_store', 'other'];

        foreach ($this->data['transactions'] as $index => $transaction) {
            foreach ($required as $field) {
                if (!isset($transaction[$field])) {
                    $this->errors[] = "transactions[{$index}].{$field} is required";
                }
            }

            if (isset($transaction['transaction_type']) && !in_array($transaction['transaction_type'], $types)) {
                $this->errors[] = "transactions[{$index}].transaction_type must be one of: " . implode(', ', $types);
            }

            if (isset($transaction['recurring_frequency']) && !in_array($transaction['recurring_frequency'], $frequencies)) {
                $this->errors[] = "transactions[{$index}].recurring_frequency must be one of: " . implode(', ', $frequencies);
            }

            if (isset($transaction['payment_channel']) && !in_array($transaction['payment_channel'], $channels)) {
                $this->errors[] = "transactions[{$index}].payment_channel must be one of: " . implode(', ', $channels);
            }

            if (isset($transaction['amount']) && !is_numeric($transaction['amount'])) {
                $this->errors[] = "transactions[{$index}].amount must be numeric";
            }

            if (isset($transaction['transaction_date']) && !$this->isValidDate($transaction['transaction_date'])) {
                $this->errors[] = "transactions[{$index}].transaction_date must be a valid ISO date";
            }

            // Validate account reference
            if (isset($transaction['account_id']) && !is_numeric($transaction['account_id'])) {
                $this->errors[] = "transactions[{$index}].account_id must be a numeric index";
            }

            // Validate bill reference
            if (isset($transaction['bill_id']) && !is_numeric($transaction['bill_id'])) {
                $this->errors[] = "transactions[{$index}].bill_id must be a numeric index";
            }
        }
    }

    /**
     * Validate budgets array.
     */
    private function validateBudgets(): void
    {
        if (!isset($this->data['budgets']) || !is_array($this->data['budgets'])) {
            return;
        }

        $statuses = ['draft', 'active', 'completed', 'overspent'];

        foreach ($this->data['budgets'] as $index => $budget) {
            $required = ['month', 'total_income', 'bills_total', 'spending_total', 'remaining_budget', 'status'];
            foreach ($required as $field) {
                if (!isset($budget[$field])) {
                    $this->errors[] = "budgets[{$index}].{$field} is required";
                }
            }

            if (isset($budget['status']) && !in_array($budget['status'], $statuses)) {
                $this->errors[] = "budgets[{$index}].status must be one of: " . implode(', ', $statuses);
            }

            if (isset($budget['month']) && !preg_match('/^\d{4}-\d{2}$/', $budget['month'])) {
                $this->errors[] = "budgets[{$index}].month must be in YYYY-MM format";
            }

            // Validate numeric fields
            $numericFields = ['total_income', 'bills_total', 'spending_total', 'remaining_budget', 'available_to_spend'];
            foreach ($numericFields as $field) {
                if (isset($budget[$field]) && !is_numeric($budget[$field])) {
                    $this->errors[] = "budgets[{$index}].{$field} must be numeric";
                }
            }
        }
    }

    /**
     * Validate account references in transactions and bills.
     */
    private function validateAccountReferences(): void
    {
        $accountCount = count($this->data['accounts'] ?? []);

        // Validate in transactions
        if (isset($this->data['transactions'])) {
            foreach ($this->data['transactions'] as $index => $transaction) {
                if (isset($transaction['account_id'])) {
                    $accountId = (int) $transaction['account_id'];
                    if ($accountId < 0 || $accountId >= $accountCount) {
                        $this->errors[] = "transactions[{$index}].account_id references non-existent account index {$accountId}";
                    }
                }
            }
        }

        // Validate in bills
        if (isset($this->data['bills'])) {
            foreach ($this->data['bills'] as $index => $bill) {
                if (isset($bill['account_id']) && $bill['account_id'] !== null) {
                    $accountId = (int) $bill['account_id'];
                    if ($accountId < 0 || $accountId >= $accountCount) {
                        $this->errors[] = "bills[{$index}].account_id references non-existent account index {$accountId}";
                    }
                }
            }
        }
    }

    /**
     * Validate bill references in transactions.
     */
    private function validateBillReferences(): void
    {
        $billCount = count($this->data['bills'] ?? []);

        if (isset($this->data['transactions'])) {
            foreach ($this->data['transactions'] as $index => $transaction) {
                if (isset($transaction['bill_id']) && $transaction['bill_id'] !== null) {
                    $billId = (int) $transaction['bill_id'];
                    if ($billId < 0 || $billId >= $billCount) {
                        $this->errors[] = "transactions[{$index}].bill_id references non-existent bill index {$billId}";
                    }
                }
            }
        }
    }

    /**
     * Validate date ranges.
     */
    private function validateDateRanges(): void
    {
        $sixMonthsAgo = now()->subMonths(6)->startOfDay();
        $today = now()->endOfDay();

        // Validate transaction dates
        if (isset($this->data['transactions'])) {
            foreach ($this->data['transactions'] as $index => $transaction) {
                if (isset($transaction['transaction_date'])) {
                    $date = Carbon::parse($transaction['transaction_date']);
                    if ($date->lt($sixMonthsAgo) || $date->gt($today)) {
                        $this->warnings[] = "transactions[{$index}].transaction_date is outside 6-month range";
                    }
                }
            }
        }

        // Validate bill due dates
        if (isset($this->data['bills'])) {
            foreach ($this->data['bills'] as $index => $bill) {
                if (isset($bill['due_date'])) {
                    $date = Carbon::parse($bill['due_date']);
                    if ($date->lt($sixMonthsAgo->copy()->subMonths(1)) || $date->gt($today->copy()->addMonths(1))) {
                        $this->warnings[] = "bills[{$index}].due_date is outside expected range";
                    }
                }
            }
        }
    }

    /**
     * Validate amount consistency.
     */
    private function validateAmountConsistency(): void
    {
        // Check that credit card balances are negative
        if (isset($this->data['accounts'])) {
            foreach ($this->data['accounts'] as $index => $account) {
                if (($account['account_type'] ?? '') === 'credit_card') {
                    $balance = (float) ($account['balance'] ?? 0);
                    if ($balance > 0) {
                        $this->warnings[] = "accounts[{$index}].balance should be negative for credit cards (currently: {$balance})";
                    }
                }
            }
        }
    }

    /**
     * Import accounts.
     */
    private function importAccounts(): int
    {
        if (!isset($this->data['accounts']) || !is_array($this->data['accounts'])) {
            return 0;
        }

        $count = 0;
        foreach ($this->data['accounts'] as $index => $accountData) {
            try {
                // Check for duplicate plaid_account_id
                if (!empty($accountData['plaid_account_id'])) {
                    $existing = Account::where('user_id', $this->user->id)
                        ->where('plaid_account_id', $accountData['plaid_account_id'])
                        ->first();
                    
                    if ($existing) {
                        $this->accountMap[$index] = $existing->id;
                        continue; // Skip duplicate
                    }
                }

                $account = Account::create([
                    'user_id' => $this->user->id,
                    'plaid_account_id' => $accountData['plaid_account_id'],
                    'plaid_access_token' => $accountData['plaid_access_token'],
                    'plaid_item_id' => $accountData['plaid_item_id'],
                    'account_name' => $accountData['account_name'],
                    'nickname' => $accountData['nickname'] ?? null,
                    'official_name' => $accountData['official_name'] ?? null,
                    'account_type' => $accountData['account_type'],
                    'account_subtype' => $accountData['account_subtype'] ?? null,
                    'institution_id' => $accountData['institution_id'] ?? null,
                    'institution_name' => $accountData['institution_name'],
                    'balance' => $accountData['balance'] ?? 0,
                    'available_balance' => $accountData['available_balance'] ?? null,
                    'credit_limit' => $accountData['credit_limit'] ?? null,
                    'currency' => $accountData['currency'] ?? 'USD',
                    'mask' => $accountData['mask'] ?? null,
                    'account_number' => $accountData['account_number'] ?? null,
                    'routing_number' => $accountData['routing_number'] ?? null,
                    'sync_status' => $accountData['sync_status'] ?? 'synced',
                    'last_synced_at' => isset($accountData['last_synced_at']) ? Carbon::parse($accountData['last_synced_at']) : null,
                    'sync_error' => $accountData['sync_error'] ?? null,
                    'is_active' => $accountData['is_active'] ?? true,
                    'metadata' => $accountData['metadata'] ?? null,
                    'payment_due_date' => isset($accountData['payment_due_date']) ? Carbon::parse($accountData['payment_due_date']) : null,
                    'payment_due_date_source' => $accountData['payment_due_date_source'] ?? null,
                    'payment_due_day' => $accountData['payment_due_day'] ?? null,
                    'minimum_payment_amount' => $accountData['minimum_payment_amount'] ?? null,
                    'next_payment_amount' => $accountData['next_payment_amount'] ?? null,
                    'interest_rate' => $accountData['interest_rate'] ?? null,
                    'interest_rate_type' => $accountData['interest_rate_type'] ?? null,
                    'interest_rate_source' => $accountData['interest_rate_source'] ?? null,
                ]);

                $this->accountMap[$index] = $account->id;
                $count++;
            } catch (\Exception $e) {
                // Skip accounts with unique constraint violations
                if (!str_contains($e->getMessage(), 'unique') && !str_contains($e->getMessage(), 'duplicate')) {
                    throw $e; // Re-throw if it's not a duplicate
                }
                // Otherwise silently skip duplicate
            }
        }

        return $count;
    }

    /**
     * Import pay schedules.
     */
    private function importPaySchedules(): int
    {
        if (!isset($this->data['pay_schedules']) || !is_array($this->data['pay_schedules'])) {
            return 0;
        }

        $count = 0;
        foreach ($this->data['pay_schedules'] as $scheduleData) {
            try {
                PaySchedule::create([
                    'user_id' => $this->user->id,
                    'frequency' => $scheduleData['frequency'],
                    'pay_day_of_week' => $scheduleData['pay_day_of_week'] ?? null,
                    'pay_day_of_month_1' => $scheduleData['pay_day_of_month_1'] ?? null,
                    'pay_day_of_month_2' => $scheduleData['pay_day_of_month_2'] ?? null,
                    'custom_schedule' => $scheduleData['custom_schedule'] ?? null,
                    'next_pay_date' => Carbon::parse($scheduleData['next_pay_date']),
                    'gross_pay' => $scheduleData['gross_pay'] ?? null,
                    'net_pay' => $scheduleData['net_pay'],
                    'currency' => $scheduleData['currency'] ?? 'USD',
                    'employer_name' => $scheduleData['employer_name'] ?? null,
                    'is_active' => $scheduleData['is_active'] ?? true,
                    'notes' => $scheduleData['notes'] ?? null,
                    'metadata' => $scheduleData['metadata'] ?? null,
                ]);
                $count++;
            } catch (\Exception $e) {
                // Skip pay schedules with unique constraint violations
                if (!str_contains($e->getMessage(), 'unique') && !str_contains($e->getMessage(), 'duplicate')) {
                    throw $e; // Re-throw if it's not a duplicate
                }
                // Otherwise silently skip duplicate
            }
        }

        return $count;
    }

    /**
     * Import bills.
     */
    private function importBills(): int
    {
        if (!isset($this->data['bills']) || !is_array($this->data['bills'])) {
            return 0;
        }

        $count = 0;
        foreach ($this->data['bills'] as $index => $billData) {
            try {
                $accountId = null;
                if (isset($billData['account_id']) && $billData['account_id'] !== null) {
                    $accountIndex = (int) $billData['account_id'];
                    $accountId = $this->accountMap[$accountIndex] ?? null;
                }

                $bill = Bill::create([
                    'user_id' => $this->user->id,
                    'account_id' => $accountId,
                    'name' => $billData['name'],
                    'description' => $billData['description'] ?? null,
                    'amount' => $billData['amount'],
                    'currency' => $billData['currency'] ?? 'USD',
                    'due_date' => Carbon::parse($billData['due_date']),
                    'next_due_date' => Carbon::parse($billData['next_due_date']),
                    'frequency' => $billData['frequency'],
                    'frequency_value' => $billData['frequency_value'] ?? null,
                    'category' => $billData['category'],
                    'payment_status' => $billData['payment_status'],
                    'last_payment_date' => isset($billData['last_payment_date']) ? Carbon::parse($billData['last_payment_date']) : null,
                    'last_payment_amount' => $billData['last_payment_amount'] ?? null,
                    'is_autopay' => $billData['is_autopay'] ?? false,
                    'autopay_account' => $billData['autopay_account'] ?? null,
                    'payment_link' => $billData['payment_link'] ?? null,
                    'payee_name' => $billData['payee_name'] ?? null,
                    'auto_detected' => $billData['auto_detected'] ?? false,
                    'source_transaction_id' => $billData['source_transaction_id'] ?? null,
                    'detection_confidence' => $billData['detection_confidence'] ?? null,
                    'reminder_enabled' => $billData['reminder_enabled'] ?? true,
                    'reminder_days_before' => $billData['reminder_days_before'] ?? 3,
                    'notes' => $billData['notes'] ?? null,
                    'priority' => $billData['priority'] ?? 'medium',
                    'metadata' => $billData['metadata'] ?? null,
                ]);

                $this->billMap[$index] = $bill->id;
                $count++;
            } catch (\Exception $e) {
                // Skip bills with unique constraint violations
                if (!str_contains($e->getMessage(), 'unique') && !str_contains($e->getMessage(), 'duplicate')) {
                    throw $e; // Re-throw if it's not a duplicate
                }
                // Otherwise silently skip duplicate
            }
        }

        return $count;
    }

    /**
     * Import transactions.
     */
    private function importTransactions(): int
    {
        if (!isset($this->data['transactions']) || !is_array($this->data['transactions'])) {
            return 0;
        }

        $count = 0;
        foreach ($this->data['transactions'] as $transactionData) {
            try {
                $accountIndex = (int) $transactionData['account_id'];
                $accountId = $this->accountMap[$accountIndex] ?? null;

                if (!$accountId) {
                    continue; // Skip if account reference invalid
                }

                $billId = null;
                if (isset($transactionData['bill_id']) && $transactionData['bill_id'] !== null) {
                    $billIndex = (int) $transactionData['bill_id'];
                    $billId = $this->billMap[$billIndex] ?? null;
                }

                Transaction::create([
                    'user_id' => $this->user->id,
                    'account_id' => $accountId,
                    'bill_id' => $billId,
                    'plaid_transaction_id' => $transactionData['plaid_transaction_id'] ?? null,
                    'name' => $transactionData['name'],
                    'merchant_name' => $transactionData['merchant_name'] ?? null,
                    'amount' => $transactionData['amount'],
                    'iso_currency_code' => $transactionData['iso_currency_code'] ?? 'USD',
                    'transaction_date' => Carbon::parse($transactionData['transaction_date']),
                    'authorized_date' => isset($transactionData['authorized_date']) ? Carbon::parse($transactionData['authorized_date']) : null,
                    'posted_date' => isset($transactionData['posted_date']) ? Carbon::parse($transactionData['posted_date']) : null,
                    'category' => $transactionData['category'] ?? null,
                    'plaid_categories' => $transactionData['plaid_categories'] ?? null,
                    'category_id' => $transactionData['category_id'] ?? null,
                    'category_confidence' => $transactionData['category_confidence'] ?? null,
                    'transaction_type' => $transactionData['transaction_type'],
                    'pending' => $transactionData['pending'] ?? false,
                    'is_recurring' => $transactionData['is_recurring'] ?? false,
                    'recurring_frequency' => $transactionData['recurring_frequency'] ?? null,
                    'recurring_group_id' => $transactionData['recurring_group_id'] ?? null,
                    'location_address' => $transactionData['location_address'] ?? null,
                    'location_city' => $transactionData['location_city'] ?? null,
                    'location_region' => $transactionData['location_region'] ?? null,
                    'location_postal_code' => $transactionData['location_postal_code'] ?? null,
                    'location_country' => $transactionData['location_country'] ?? null,
                    'location_lat' => $transactionData['location_lat'] ?? null,
                    'location_lon' => $transactionData['location_lon'] ?? null,
                    'payment_channel' => $transactionData['payment_channel'] ?? null,
                    'user_category' => $transactionData['user_category'] ?? null,
                    'user_notes' => $transactionData['user_notes'] ?? null,
                    'tags' => $transactionData['tags'] ?? null,
                    'metadata' => $transactionData['metadata'] ?? null,
                ]);
                $count++;
            } catch (\Exception $e) {
                // Skip transactions with unique constraint violations
                if (!str_contains($e->getMessage(), 'unique') && !str_contains($e->getMessage(), 'duplicate')) {
                    throw $e; // Re-throw if it's not a duplicate
                }
                // Otherwise silently skip duplicate
            }
        }

        return $count;
    }

    /**
     * Import budgets.
     */
    private function importBudgets(): int
    {
        if (!isset($this->data['budgets']) || !is_array($this->data['budgets'])) {
            return 0;
        }

        $count = 0;
        foreach ($this->data['budgets'] as $budgetData) {
            try {
                // Check if budget already exists for this user and month
                $existing = Budget::where('user_id', $this->user->id)
                    ->where('month', $budgetData['month'])
                    ->first();

                if ($existing) {
                    continue; // Skip duplicate
                }

                Budget::create([
                    'user_id' => $this->user->id,
                    'month' => $budgetData['month'],
                    'total_income' => $budgetData['total_income'],
                    'expected_income' => $budgetData['expected_income'] ?? null,
                    'bills_total' => $budgetData['bills_total'],
                    'bills_paid' => $budgetData['bills_paid'] ?? 0,
                    'bills_pending' => $budgetData['bills_pending'] ?? 0,
                    'spending_total' => $budgetData['spending_total'],
                    'spending_by_category' => $budgetData['spending_by_category'] ?? null,
                    'category_breakdown' => $budgetData['category_breakdown'] ?? null,
                    'rent_allocation' => $budgetData['rent_allocation'] ?? null,
                    'rent_paid' => $budgetData['rent_paid'] ?? false,
                    'remaining_budget' => $budgetData['remaining_budget'],
                    'available_to_spend' => $budgetData['available_to_spend'] ?? 0,
                    'savings_goal' => $budgetData['savings_goal'] ?? null,
                    'savings_actual' => $budgetData['savings_actual'] ?? 0,
                    'spending_limit' => $budgetData['spending_limit'] ?? null,
                    'status' => $budgetData['status'],
                    'transactions_count' => $budgetData['transactions_count'] ?? 0,
                    'bills_count' => $budgetData['bills_count'] ?? 0,
                    'notes' => $budgetData['notes'] ?? null,
                    'metadata' => $budgetData['metadata'] ?? null,
                ]);
                $count++;
            } catch (\Exception $e) {
                // Skip budgets with unique constraint violations
                if (!str_contains($e->getMessage(), 'unique') && !str_contains($e->getMessage(), 'duplicate')) {
                    throw $e; // Re-throw if it's not a duplicate
                }
                // Otherwise silently skip duplicate
            }
        }

        return $count;
    }

    /**
     * Check if string is a valid date.
     */
    private function isValidDate(?string $date): bool
    {
        if ($date === null || $date === '') {
            return false;
        }

        try {
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get validation summary.
     */
    private function getSummary(): array
    {
        return [
            'accounts' => count($this->data['accounts'] ?? []),
            'transactions' => count($this->data['transactions'] ?? []),
            'bills' => count($this->data['bills'] ?? []),
            'pay_schedules' => count($this->data['pay_schedules'] ?? []),
            'budgets' => count($this->data['budgets'] ?? []),
            'errors_count' => count($this->errors),
            'warnings_count' => count($this->warnings),
        ];
    }
}

