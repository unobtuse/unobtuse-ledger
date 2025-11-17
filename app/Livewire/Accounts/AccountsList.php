<?php

declare(strict_types=1);

namespace App\Livewire\Accounts;

use App\Jobs\SyncTellerTransactions;
use App\Models\Account;
use App\Services\TellerService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Accounts List Livewire Component
 * 
 * Provides comprehensive account management with filtering, grouping,
 * card-grid layout, and account actions.
 */
class AccountsList extends Component
{
    protected TellerService $tellerService;

    // Filters
    public string $search = '';
    public string $typeFilter = 'all'; // 'all', 'checking', 'savings', 'credit_card', 'investment', 'loan', 'other'
    public string $statusFilter = 'all'; // 'all', 'active', 'syncing', 'failed', 'disabled'
    public bool $groupByInstitution = false;
    public bool $groupByType = true; // Default to grouping by type
    
    // Modal states
    public bool $showNicknameModal = false;
    public bool $showDisconnectModal = false;
    public bool $showDueDateModal = false;
    public bool $showWebsiteUrlModal = false;
    public bool $showInitialLoanAmountModal = false;
    public ?string $selectedAccountId = null;
    
    // Form data
    public string $nickname = '';
    public ?string $paymentDueDate = null;
    public ?int $paymentDueDay = null;
    public ?string $paymentAmount = null;
    public ?string $interestRate = null;
    public ?string $interestRateType = null;
    public string $websiteUrl = '';
    public string $initialLoanAmount = '';
    public string $loanInterestRate = '';
    public string $loanTermMonths = '';
    public string $loanFirstPaymentDate = '';
    public string $loanOriginationFees = '';
    
    // Expanded accounts (for card details)
    public array $expandedAccounts = [];

    /**
     * Bootstrap component dependencies.
     */
    public function boot(TellerService $tellerService): void
    {
        $this->tellerService = $tellerService;
    }
    
    /**
     * Toggle account expansion
     */
    public function toggleExpand(string $accountId): void
    {
        if (in_array($accountId, $this->expandedAccounts)) {
            $this->expandedAccounts = array_diff($this->expandedAccounts, [$accountId]);
        } else {
            $this->expandedAccounts[] = $accountId;
        }
    }
    
    /**
     * Check if account is expanded
     */
    public function isExpanded(string $accountId): bool
    {
        return in_array($accountId, $this->expandedAccounts);
    }
    
    /**
     * Refresh account balance
     */
    public function refreshBalance(string $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        try {
            // Get balance from Teller
            $result = $this->tellerService->getBalance($account->teller_token, $account->teller_account_id);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to fetch balance');
            }

            $balance = $result['balance'];
            $updateData = [
                'last_synced_at' => now(),
                'sync_status' => 'synced',
            ];

            // Extract balance data using account type-aware logic
            $balanceData = $this->tellerService->extractBalanceForAccountType(
                $balance,
                $account->account_type
            );

            // Merge balance data into update array
            $updateData = array_merge($updateData, $balanceData);

            // Extract credit card specific fields (liabilities) with source tracking
            if ($account->account_type === 'credit_card' || $account->account_type === 'credit') {
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

            $account->update($updateData);

            session()->flash('success', 'Balance refreshed successfully!');
        } catch (\Exception $e) {
            Log::error('Livewire refresh balance failed', [
                'account_id' => $account->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to refresh balance: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync account transactions
     */
    public function syncAccount(string $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        try {
            SyncTellerTransactions::dispatch($account);
            session()->flash('success', 'Transaction sync started. This may take a few moments.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start sync.');
        }
    }
    
    /**
     * Open nickname edit modal
     */
    public function editNickname(string $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $this->selectedAccountId = $accountId;
        $this->nickname = $account->nickname ?? '';
        $this->showNicknameModal = true;
    }
    
    /**
     * Save nickname
     */
    public function saveNickname(): void
    {
        if (!$this->selectedAccountId) {
            return;
        }
        
        $account = Account::where('id', $this->selectedAccountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        try {
            $account->update([
                'nickname' => $this->nickname !== '' ? $this->nickname : null,
            ]);

            session()->flash('success', 'Nickname updated successfully!');
            $this->closeNicknameModal();
        } catch (\Exception $e) {
            Log::error('Livewire nickname update failed', [
                'account_id' => $account->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to update nickname.');
        }
    }
    
    /**
     * Close nickname modal
     */
    public function closeNicknameModal(): void
    {
        $this->showNicknameModal = false;
        $this->selectedAccountId = null;
        $this->nickname = '';
    }
    
    /**
     * Open disconnect confirmation modal
     */
    public function confirmDisconnect(string $accountId): void
    {
        $this->selectedAccountId = $accountId;
        $this->showDisconnectModal = true;
    }
    
    /**
     * Disconnect account
     */
    public function disconnectAccount(): void
    {
        if (!$this->selectedAccountId) {
            return;
        }
        
        $account = Account::where('id', $this->selectedAccountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        try {
            // Disconnect from Teller API if we have a Teller token
            if ($account->teller_token) {
                $disconnectResult = $this->tellerService->disconnect($account->teller_token);
                
                if (!$disconnectResult['success']) {
                    Log::warning('Teller disconnect API call failed, but continuing with local deletion', [
                        'account_id' => $account->id,
                        'user_id' => auth()->id(),
                        'error' => $disconnectResult['error'] ?? 'Unknown error',
                    ]);
                }
            }

            $account->update(['is_active' => false]);
            $account->delete();

            session()->flash('success', 'Account disconnected successfully.');
            $this->expandedAccounts = array_diff($this->expandedAccounts, [$this->selectedAccountId]);
            $this->closeDisconnectModal();
        } catch (\Exception $e) {
            Log::error('Livewire disconnect account failed', [
                'account_id' => $account->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to disconnect account.');
        }
    }
    
    /**
     * Close disconnect modal
     */
    public function closeDisconnectModal(): void
    {
        $this->showDisconnectModal = false;
        $this->selectedAccountId = null;
    }
    
    /**
     * Open due date edit modal
     */
    public function openEditDueDate(string $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $this->selectedAccountId = $accountId;
        $this->paymentDueDate = $account->payment_due_date?->format('Y-m-d');
        $this->paymentDueDay = $account->payment_due_day;
        $this->paymentAmount = $account->next_payment_amount ?? $account->minimum_payment_amount 
            ? number_format((float) ($account->next_payment_amount ?? $account->minimum_payment_amount), 2) 
            : null;
        $this->interestRate = $account->interest_rate 
            ? number_format((float) $account->interest_rate, 2) 
            : null;
        $this->interestRateType = $account->interest_rate_type;
        $this->showDueDateModal = true;
    }
    
    /**
     * Save due date and liability information
     */
    public function saveDueDate(): void
    {
        if (!$this->selectedAccountId) {
            return;
        }
        
        $account = Account::where('id', $this->selectedAccountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $this->validate([
            'paymentDueDate' => 'nullable|date',
            'paymentDueDay' => 'nullable|integer|min:1|max:31',
            'paymentAmount' => 'nullable|numeric|min:0',
            'interestRate' => 'nullable|numeric|min:0|max:100',
            'interestRateType' => 'nullable|in:fixed,variable',
        ]);
        
        try {
            $updateData = [
                'payment_due_date_source' => 'manual',
            ];
            
            if ($this->paymentDueDate) {
                $updateData['payment_due_date'] = $this->paymentDueDate;
            } else {
                $updateData['payment_due_date'] = null;
            }
            
            if ($this->paymentDueDay) {
                $updateData['payment_due_day'] = $this->paymentDueDay;
            } else {
                $updateData['payment_due_day'] = null;
            }
            
            // Set payment amount based on account type
            if ($this->paymentAmount !== null && $this->paymentAmount !== '') {
                if ($account->account_type === 'credit_card') {
                    $updateData['minimum_payment_amount'] = $this->paymentAmount;
                } else {
                    $updateData['next_payment_amount'] = $this->paymentAmount;
                }
            }
            
            if ($this->interestRate !== null && $this->interestRate !== '') {
                $updateData['interest_rate'] = $this->interestRate;
                $updateData['interest_rate_source'] = 'manual';
                if ($this->interestRateType) {
                    $updateData['interest_rate_type'] = $this->interestRateType;
                }
            }
            
            $account->update($updateData);

            session()->flash('success', 'Due date updated successfully!');
            $this->closeDueDateModal();
        } catch (\Exception $e) {
            Log::error('Livewire due date update failed', [
                'account_id' => $account->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to update due date.');
        }
    }
    
    /**
     * Close due date modal
     */
    public function closeDueDateModal(): void
    {
        $this->showDueDateModal = false;
        $this->selectedAccountId = null;
        $this->paymentDueDate = null;
        $this->paymentDueDay = null;
        $this->paymentAmount = null;
        $this->interestRate = null;
        $this->interestRateType = null;
    }
    
    /**
     * Open website URL edit modal
     */
    public function openEditWebsiteUrl(string $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $this->selectedAccountId = $accountId;
        $this->websiteUrl = $account->institution_url ?? '';
        $this->showWebsiteUrlModal = true;
    }
    
    /**
     * Save website URL
     */
    public function saveWebsiteUrl(): void
    {
        if (!$this->selectedAccountId) {
            return;
        }
        
        $account = Account::where('id', $this->selectedAccountId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $this->validate([
            'websiteUrl' => 'nullable|url|max:255',
        ]);
        
        try {
            $account->update([
                'institution_url' => $this->websiteUrl !== '' ? $this->websiteUrl : null,
            ]);

            session()->flash('success', 'Website URL updated successfully!');
            $this->closeWebsiteUrlModal();
        } catch (\Exception $e) {
            Log::error('Livewire website URL update failed', [
                'account_id' => $account->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to update website URL.');
        }
    }
    
    /**
     * Close website URL modal
     */
    public function closeWebsiteUrlModal(): void
    {
        $this->showWebsiteUrlModal = false;
        $this->selectedAccountId = null;
        $this->websiteUrl = '';
    }

    /**
     * Open initial loan amount modal
     */
    public function openInitialLoanAmountModal(string $accountId): void
    {
        $account = Account::findOrFail($accountId);
        $this->selectedAccountId = $accountId;
        $this->initialLoanAmount = $account->initial_loan_amount ? (string) $account->initial_loan_amount : '';
        $this->loanInterestRate = $account->loan_interest_rate ? (string) $account->loan_interest_rate : '';
        $this->loanTermMonths = $account->loan_term_months ? (string) $account->loan_term_months : '';
        $this->loanFirstPaymentDate = $account->loan_first_payment_date ? $account->loan_first_payment_date->format('Y-m-d') : '';
        $this->loanOriginationFees = $account->loan_origination_fees ? (string) $account->loan_origination_fees : '';
        $this->showInitialLoanAmountModal = true;
    }

    /**
     * Save initial loan amount
     */
    public function saveInitialLoanAmount(): void
    {
        $this->validate([
            'initialLoanAmount' => 'required|numeric|min:0',
            'loanInterestRate' => 'nullable|numeric|min:0|max:100',
            'loanTermMonths' => 'nullable|integer|min:1|max:600',
            'loanFirstPaymentDate' => 'nullable|date',
            'loanOriginationFees' => 'nullable|numeric|min:0',
        ]);

        $account = Account::findOrFail($this->selectedAccountId);
        $account->update([
            'initial_loan_amount' => $this->initialLoanAmount > 0 ? $this->initialLoanAmount : null,
            'loan_interest_rate' => $this->loanInterestRate !== '' ? $this->loanInterestRate : null,
            'loan_term_months' => $this->loanTermMonths !== '' ? $this->loanTermMonths : null,
            'loan_first_payment_date' => $this->loanFirstPaymentDate !== '' ? $this->loanFirstPaymentDate : null,
            'loan_origination_fees' => $this->loanOriginationFees !== '' ? $this->loanOriginationFees : null,
        ]);

        $this->closeInitialLoanAmountModal();
        $this->dispatch('account-updated');
    }

    /**
     * Close initial loan amount modal
     */
    public function closeInitialLoanAmountModal(): void
    {
        $this->showInitialLoanAmountModal = false;
        $this->selectedAccountId = null;
        $this->initialLoanAmount = '';
        $this->loanInterestRate = '';
        $this->loanTermMonths = '';
        $this->loanFirstPaymentDate = '';
        $this->loanOriginationFees = '';
    }
    
    /**
     * Toggle institution grouping
     */
    public function toggleGrouping(): void
    {
        $this->groupByInstitution = !$this->groupByInstitution;
    }
    
    /**
     * Filter by account type
     */
    public function filterByType(string $type): void
    {
        $this->typeFilter = $type;
    }
    
    /**
     * Get accounts query with filters
     */
    protected function getAccountsQuery(): Builder
    {
        $query = Account::query()
            ->with('institution') // Eager load institution for logos
            ->where('user_id', auth()->id())
            ->where('is_active', true);
        
        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('account_name', 'ilike', '%' . $this->search . '%')
                  ->orWhere('nickname', 'ilike', '%' . $this->search . '%')
                  ->orWhere('institution_name', 'ilike', '%' . $this->search . '%')
                  ->orWhere('mask', 'ilike', '%' . $this->search . '%');
            });
        }
        
        // Type filter
        if ($this->typeFilter !== 'all') {
            // For 'loan' filter, include all loan types
            if ($this->typeFilter === 'loan') {
                $query->whereIn('account_type', ['loan', 'auto_loan', 'mortgage', 'student_loan']);
            } else {
                $query->where('account_type', $this->typeFilter);
            }
        }
        
        // Status filter
        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'active') {
                $query->where('sync_status', 'synced');
            } else {
                $query->where('sync_status', $this->statusFilter);
            }
        }
        
        // Order by institution name, then account name
        $query->orderBy('institution_name')
              ->orderBy('account_name');
        
        return $query;
    }
    
    /**
     * Get filtered accounts
     */
    protected function getAccounts(): Collection
    {
        return $this->getAccountsQuery()->get();
    }
    
    /**
     * Get accounts grouped by institution
     */
    protected function getGroupedAccounts(): array
    {
        $accounts = $this->getAccounts();
        $grouped = [];
        
        foreach ($accounts as $account) {
            $institution = $account->institution_name ?? 'Other';
            if (!isset($grouped[$institution])) {
                $grouped[$institution] = [];
            }
            $grouped[$institution][] = $account;
        }
        
        return $grouped;
    }
    
    /**
     * Get summary statistics
     */
    protected function getSummaryStats(): array
    {
        $accounts = Account::where('user_id', auth()->id())
            ->where('is_active', true)
            ->get();
        
        $totalBalance = $accounts->sum('balance');
        $activeCount = $accounts->where('sync_status', 'synced')->count();
        $syncingCount = $accounts->where('sync_status', 'syncing')->count();
        $failedCount = $accounts->where('sync_status', 'failed')->count();
        
        $lastSync = $accounts->whereNotNull('last_synced_at')
            ->max('last_synced_at');
        
        return [
            'total_balance' => $totalBalance,
            'active_count' => $activeCount,
            'syncing_count' => $syncingCount,
            'failed_count' => $failedCount,
            'total_count' => $accounts->count(),
            'last_sync' => $lastSync,
        ];
    }
    
    /**
     * Get selected account
     */
    protected function getSelectedAccount(): ?Account
    {
        if (!$this->selectedAccountId) {
            return null;
        }
        
        return Account::where('id', $this->selectedAccountId)
            ->where('user_id', auth()->id())
            ->first();
    }
    
    /**
     * Get accounts grouped by type
     */
    protected function getGroupedByType(): array
    {
        $accounts = $this->getAccounts();
        $grouped = [];
        
        // Define group order
        $groupOrder = [
            'Checking & Savings' => 1,
            'Loans' => 2,
            'Credit Cards' => 3,
            'Investments' => 4,
            'Other' => 5,
        ];
        
        foreach ($accounts as $account) {
            // Determine group based on account type
            $group = match($account->account_type) {
                'checking', 'savings' => 'Checking & Savings',
                'loan', 'auto_loan', 'mortgage', 'student_loan' => 'Loans',
                'credit_card' => 'Credit Cards',
                'investment' => 'Investments',
                default => 'Other'
            };
            
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $account;
        }
        
        // Sort groups by predefined order
        uksort($grouped, function($a, $b) use ($groupOrder) {
            return ($groupOrder[$a] ?? 99) <=> ($groupOrder[$b] ?? 99);
        });
        
        return $grouped;
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        return view('livewire.accounts.accounts-list', [
            'accounts' => $this->getAccounts(),
            'groupedAccounts' => $this->getGroupedAccounts(),
            'groupedByType' => $this->getGroupedByType(),
            'summaryStats' => $this->getSummaryStats(),
            'selectedAccount' => $this->getSelectedAccount(),
        ]);
    }
}
