<?php

declare(strict_types=1);

namespace App\Livewire\Accounts;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\StatementParserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Manual Account Upload Component
 * 
 * Handles AI-powered parsing of bank statements (PDFs/images)
 * to create manual accounts and import transactions.
 */
class ManualAccountUpload extends Component
{
    use WithFileUploads;
    
    // Modal state
    public bool $showUploadModal = false;
    public bool $showPreviewModal = false;
    
    // Upload step
    public $statementFile;
    public bool $isProcessing = false;
    public ?string $errorMessage = null;
    public int $statementYear;
    
    // Parsed data
    public ?array $parsedAccount = null;
    public ?array $parsedTransactions = null;
    public ?array $duplicateTransactionIndices = null;
    public ?array $suggestedDates = null; // Cross-referenced actual payment dates
    
    // User editable fields
    public string $institutionName = '';
    public string $accountName = '';
    public string $accountNumberLast4 = '';
    public string $accountType = 'credit_card';
    public string $currency = 'USD';
    public float $endingBalance = 0;
    public float $availableBalance = 0;
    public float $creditLimit = 0;
    
    protected StatementParserService $parserService;
    
    /**
     * Boot component dependencies
     */
    public function boot(StatementParserService $parserService): void
    {
        $this->parserService = $parserService;
    }
    
    // Existing account ID for updates
    public ?string $existingAccountId = null;
    
    /**
     * Listen for events
     */
    protected $listeners = [
        'openManualUpload' => 'openUploadModal',
        'openManualUploadForAccount' => 'openUploadModalForAccount',
        'account-created' => '$refresh',
    ];
    
    /**
     * Open upload modal
     */
    public function openUploadModal(): void
    {
        $this->existingAccountId = null;
        $this->statementYear = (int) date('Y'); // Default to current year
        $this->reset([
            'statementFile',
            'isProcessing',
            'errorMessage',
            'parsedAccount',
            'parsedTransactions',
            'institutionName',
            'accountName',
            'accountNumberLast4',
            'accountType',
            'currency',
            'endingBalance',
            'availableBalance',
            'creditLimit',
        ]);
        $this->showUploadModal = true;
    }
    
    /**
     * Open upload modal for existing account update
     */
    public function openUploadModalForAccount(string $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('user_id', auth()->id())
            ->where('is_manual', true)
            ->first();
        
        if (!$account) {
            $this->errorMessage = 'Account not found';
            return;
        }
        
        // Store existing account ID
        $this->existingAccountId = $accountId;
        
        // Pre-fill with existing account data
        $this->institutionName = $account->institution_name;
        $this->accountName = $account->account_name;
        $this->accountNumberLast4 = $account->mask ?? '';
        $this->accountType = $account->account_type;
        $this->currency = $account->currency;
        $this->endingBalance = (float) $account->balance;
        $this->availableBalance = (float) ($account->available_balance ?? 0);
        $this->creditLimit = (float) ($account->credit_limit ?? 0);
        $this->statementYear = (int) date('Y'); // Default to current year
        
        $this->reset([
            'statementFile',
            'isProcessing',
            'errorMessage',
            'parsedAccount',
            'parsedTransactions',
        ]);
        
        $this->showUploadModal = true;
    }
    
    /**
     * Close upload modal
     */
    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->reset(['statementFile', 'errorMessage']);
    }
    
    /**
     * Process uploaded statement with AI
     */
    public function processStatement(): void
    {
        $this->validate([
            'statementFile' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:10240', // Max 10MB
        ]);
        
        $this->isProcessing = true;
        $this->errorMessage = null;
        
        try {
            // Store file temporarily
            $path = $this->statementFile->store('temp-statements', 'local');
            $extension = $this->statementFile->getClientOriginalExtension();
            
            // Parse with AI (pass the selected year for context)
            $result = $this->parserService->parseStatement($path, $extension, $this->statementYear);
            
            // Clean up temp file
            Storage::disk('local')->delete($path);
            
            if (!$result['success']) {
                $this->errorMessage = $result['error'];
                $this->isProcessing = false;
                return;
            }
            
            // Store parsed data
            $data = $result['data'];
            $this->parsedAccount = $data['account'];
            $this->parsedTransactions = $data['transactions'];
            
            // Log what AI extracted
            $txnCount = count($this->parsedTransactions ?? []);
            $firstFive = array_slice($this->parsedTransactions ?? [], 0, 5);
            $lastTen = $txnCount > 10 ? array_slice($this->parsedTransactions ?? [], -10) : [];
            
            Log::info('AI extracted data', [
                'account' => $this->parsedAccount,
                'transaction_count' => $txnCount,
                'first_5_transactions' => $firstFive,
                'last_10_transactions' => $lastTen // October transactions likely here
            ]);
            
            // Check for duplicates against existing account (match on date + amount only)
            $this->duplicateTransactionIndices = [];
            
            Log::info('Duplicate check', [
                'existingAccountId' => $this->existingAccountId,
                'has_transactions' => !empty($this->parsedTransactions),
                'txn_count' => count($this->parsedTransactions ?? [])
            ]);
            
            if ($this->existingAccountId && $this->parsedTransactions) {
                $account = Account::find($this->existingAccountId);
                
                Log::info('Checking duplicates for account', [
                    'account_id' => $account?->id,
                    'account_name' => $account?->account_name,
                    'existing_txn_count' => $account ? Transaction::where('account_id', $account->id)->count() : 0
                ]);
                
                if ($account) {
                    $duplicateCount = 0;
                    foreach ($this->parsedTransactions as $index => $txn) {
                        if (empty($txn['date']) || !isset($txn['amount'])) {
                            continue;
                        }
                        
                        // Match on date + amount only (descriptions may vary between statement and screenshot)
                        $existingTransaction = Transaction::where('account_id', $account->id)
                            ->where('transaction_date', $txn['date'])
                            ->where('amount', (float) $txn['amount'])
                            ->first();
                        
                        if ($existingTransaction) {
                            $this->duplicateTransactionIndices[] = $index;
                            $duplicateCount++;
                            
                            // Log first few duplicates for debugging
                            if ($duplicateCount <= 3) {
                                Log::info('Duplicate found', [
                                    'index' => $index,
                                    'parsed_date' => $txn['date'],
                                    'parsed_amount' => $txn['amount'],
                                    'parsed_desc' => substr($txn['description'] ?? '', 0, 50),
                                    'existing_date' => $existingTransaction->transaction_date,
                                    'existing_amount' => $existingTransaction->amount,
                                    'existing_desc' => substr($existingTransaction->name, 0, 50)
                                ]);
                            }
                        }
                    }
                    
                    Log::info('Duplicate detection complete', [
                        'duplicates_found' => $duplicateCount,
                        'total_parsed' => count($this->parsedTransactions)
                    ]);
                }
            }
            
            // Cross-reference loan payments with other accounts (Option 3: Smart Detection)
            $this->crossReferenceLoanPayments();
            
            // Pre-fill form fields (only if not updating existing account)
            if (!$this->existingAccountId) {
                $this->institutionName = $this->parsedAccount['institution_name'] ?? '';
                
                // If account name is empty, create a default based on account type
                $accountName = $this->parsedAccount['account_name'] ?? '';
                if (empty($accountName)) {
                    $accountType = $this->parsedAccount['account_type'] ?? '';
                    $accountName = match($accountType) {
                        'auto_loan' => 'Auto Loan',
                        'mortgage' => 'Mortgage',
                        'student_loan' => 'Student Loan',
                        'loan' => 'Personal Loan',
                        'credit_card' => 'Credit Card',
                        'checking' => 'Checking',
                        'savings' => 'Savings',
                        'investment' => 'Investment Account',
                        default => 'Account'
                    };
                }
                $this->accountName = $accountName;
                
                // Ensure only last 4 digits
                $accountNumber = $this->parsedAccount['account_number_last4'] ?? '';
                $this->accountNumberLast4 = substr($accountNumber, -4);
                
                $this->accountType = $this->parsedAccount['account_type'] ?? 'credit_card';
                $this->currency = $this->parsedAccount['currency'] ?? 'USD';
            }
            
            // Always update balance info from parsed statement
            $this->endingBalance = (float) ($this->parsedAccount['ending_balance'] ?? 0);
            
            // Only set available balance if it's actually present and non-zero (leave empty otherwise)
            $availBal = (float) ($this->parsedAccount['available_balance'] ?? 0);
            $this->availableBalance = $availBal > 0 ? $availBal : 0.0; // Will be cleared in UI if user wants
            
            // Only set credit limit if present and non-zero (leave empty otherwise)
            $creditLim = (float) ($this->parsedAccount['credit_limit'] ?? 0);
            $this->creditLimit = $creditLim > 0 ? $creditLim : 0.0; // Will be cleared in UI if user wants
            
            // Move to preview
            $this->showUploadModal = false;
            $this->showPreviewModal = true;
            
        } catch (\Exception $e) {
            Log::error('Statement processing failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            $this->errorMessage = 'Failed to process statement: ' . $e->getMessage();
        }
        
        $this->isProcessing = false;
    }
    
    /**
     * Close preview modal
     */
    public function closePreviewModal(): void
    {
        $this->showPreviewModal = false;
        $this->reset([
            'parsedAccount',
            'parsedTransactions',
            'institutionName',
            'accountName',
            'accountNumberLast4',
            'accountType',
            'currency',
            'endingBalance',
            'availableBalance',
            'creditLimit'
        ]);
    }
    
    /**
     * Cross-reference loan payments with other accounts to find actual payment dates
     */
    protected function crossReferenceLoanPayments(): void
    {
        $this->suggestedDates = [];
        
        // Only for loan accounts
        $accountType = $this->parsedAccount['account_type'] ?? '';
        if (!in_array($accountType, ['loan', 'auto_loan', 'mortgage', 'student_loan'])) {
            return;
        }
        
        if (!$this->parsedTransactions) {
            return;
        }
        
        // Get all user's accounts (to search for matching payments)
        $query = Account::where('user_id', auth()->id());
        
        // Exclude existing account if updating
        if ($this->existingAccountId) {
            $query->where('id', '!=', $this->existingAccountId);
        }
        
        $allAccounts = $query->pluck('id');
        
        if ($allAccounts->isEmpty()) {
            return;
        }
        
        // For each parsed transaction, look for matching payment in other accounts
        foreach ($this->parsedTransactions as $index => $txn) {
            if (empty($txn['date']) || !isset($txn['amount'])) {
                continue;
            }
            
            $parsedDate = \Carbon\Carbon::parse($txn['date']);
            $amount = abs((float) $txn['amount']); // Use absolute value for matching
            
            // Search for payment within ±7 days with matching amount
            $matchingPayment = Transaction::whereIn('account_id', $allAccounts)
                ->whereBetween('transaction_date', [
                    $parsedDate->copy()->subDays(7),
                    $parsedDate->copy()->addDays(7)
                ])
                ->where(function($query) use ($amount) {
                    $query->where('amount', -$amount) // Outgoing payment
                          ->orWhere('amount', $amount); // Or positive (depending on account type)
                })
                ->with('account')
                ->first();
            
            if ($matchingPayment) {
                $this->suggestedDates[$index] = [
                    'suggested_date' => $matchingPayment->transaction_date->format('Y-m-d'),
                    'original_date' => $txn['date'],
                    'from_account' => $matchingPayment->account->institution_name . ' - ' . $matchingPayment->account->account_name,
                    'amount' => $amount,
                ];
            }
        }
        
        if (count($this->suggestedDates) > 0) {
            Log::info('Cross-referenced loan payments', [
                'suggestions_found' => count($this->suggestedDates),
                'suggestions' => $this->suggestedDates
            ]);
        }
    }
    
    /**
     * Accept suggested date for a transaction
     */
    public function acceptSuggestedDate(int $index): void
    {
        if (isset($this->suggestedDates[$index]) && isset($this->parsedTransactions[$index])) {
            $this->parsedTransactions[$index]['date'] = $this->suggestedDates[$index]['suggested_date'];
            unset($this->suggestedDates[$index]);
        }
    }
    
    /**
     * Remove a transaction from the list
     */
    public function removeTransaction(int $index): void
    {
        if (isset($this->parsedTransactions[$index])) {
            unset($this->parsedTransactions[$index]);
            // Re-index array to maintain sequential keys
            $this->parsedTransactions = array_values($this->parsedTransactions);
        }
    }
    
    /**
     * Save manual account and transactions
     */
    public function saveManualAccount(): void
    {
        // Debug log
        Log::info('Attempting to save manual account', [
            'institutionName' => $this->institutionName,
            'accountName' => $this->accountName,
            'accountType' => $this->accountType,
            'endingBalance' => $this->endingBalance,
            'has_transactions' => !empty($this->parsedTransactions),
        ]);
        
        $this->validate([
            'institutionName' => 'required|string|max:255',
            'accountName' => 'required|string|max:255',
            'accountType' => 'required|string|in:checking,savings,credit_card,investment,loan,auto_loan,mortgage,student_loan',
            'endingBalance' => 'nullable|numeric',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Update existing account or create new one
            if ($this->existingAccountId) {
                $account = Account::where('id', $this->existingAccountId)
                    ->where('user_id', auth()->id())
                    ->where('is_manual', true)
                    ->firstOrFail();
                
                // Update account details
                $account->update([
                    'balance' => $this->endingBalance,
                    'available_balance' => ($this->availableBalance > 0 && $this->availableBalance != $this->endingBalance) ? $this->availableBalance : null,
                    'credit_limit' => $this->creditLimit > 0 ? $this->creditLimit : null,
                    'last_synced_at' => now(),
                ]);
            } else {
                // Create new manual account
                $account = Account::create([
                'user_id' => auth()->id(),
                'institution_name' => $this->institutionName,
                'account_name' => $this->accountName,
                'nickname' => null,
                'account_type' => $this->accountType,
                'mask' => substr($this->accountNumberLast4, -4), // Ensure only last 4
                'balance' => $this->endingBalance,
                'available_balance' => ($this->availableBalance > 0 && $this->availableBalance != $this->endingBalance) ? $this->availableBalance : null,
                'credit_limit' => $this->creditLimit > 0 ? $this->creditLimit : null,
                'initial_loan_amount' => $this->initialLoanAmount > 0 ? $this->initialLoanAmount : null,
                'currency' => $this->currency,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'is_active' => true,
                'is_manual' => true,
                ]);
            }
            
            // Create transactions with duplicate detection
            $transactionCount = 0;
            $duplicateCount = 0;
            
            if ($this->parsedTransactions && is_array($this->parsedTransactions)) {
                foreach ($this->parsedTransactions as $txn) {
                    if (empty($txn['date']) || empty($txn['description']) || !isset($txn['amount'])) {
                        continue; // Skip invalid transactions
                    }
                    
                    // Check for duplicate transaction (same account, date, amount, description)
                    $existingTransaction = Transaction::where('account_id', $account->id)
                        ->where('transaction_date', $txn['date'])
                        ->where('amount', (float) $txn['amount'])
                        ->where('name', $txn['description'])
                        ->first();
                    
                    if ($existingTransaction) {
                        $duplicateCount++;
                        continue; // Skip duplicate
                    }
                    
                    Transaction::create([
                        'user_id' => auth()->id(),
                        'account_id' => $account->id,
                        'name' => $txn['description'],
                        'merchant_name' => $txn['description'],
                        'amount' => (float) $txn['amount'],
                        'transaction_date' => $txn['date'],
                        'iso_currency_code' => $this->currency,
                        'transaction_type' => $txn['type'] ?? 'debit',
                        'pending' => false,
                        'is_manual' => true,
                    ]);
                    
                    $transactionCount++;
                }
            }
            
            DB::commit();
            
            if ($this->existingAccountId) {
                $message = "Account '{$this->accountName}' updated with {$transactionCount} new transactions!";
            } else {
                $message = "Manual account '{$this->accountName}' created with {$transactionCount} transactions!";
            }
            
            if ($duplicateCount > 0) {
                $message .= " ({$duplicateCount} duplicates skipped)";
            }
            
            session()->flash('success', $message);
            
            $this->closePreviewModal();
            $this->dispatch('account-created');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to save manual account', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            session()->flash('error', 'Failed to save account: ' . $e->getMessage());
        }
    }
    
    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.accounts.manual-account-upload');
    }
}
