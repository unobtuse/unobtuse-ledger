<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Transactions List Livewire Component
 * 
 * Provides real-time filtering, searching, and sorting of transactions
 * without page reloads. Includes summary statistics and detailed transaction views.
 */
class TransactionsList extends Component
{
    use WithPagination;

    // Search and filters
    public string $search = '';
    public string $accountFilter = '';
    public string $categoryFilter = '';
    public string $typeFilter = ''; // 'debit' or 'credit'
    public string $dateFrom = '';
    public string $dateTo = '';
    public float $amountMin = 0;
    public float $amountMax = 0;
    
    // Sorting
    public string $sortField = 'transaction_date';
    public string $sortDirection = 'desc';
    
    // Pagination
    public int $perPage = 25;
    
    // Recurring filter
    public string $recurringFilter = '';
    
    // Bulk selection
    public array $selectedTransactions = [];
    public bool $selectAll = false;
    
    // Modal state
    public bool $showDetailsModal = false;
    public ?string $selectedTransactionId = null;
    public bool $showEditCategoryModal = false;
    public bool $showCreateTransactionModal = false;
    
    /**
     * Reset pagination when filters change
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    
    public function updatingAccountFilter(): void
    {
        $this->resetPage();
    }
    
    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }
    
    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }
    
    public function updatingRecurringFilter(): void
    {
        $this->resetPage();
    }
    
    /**
     * Sort by a specific field
     */
    public function sortBy(string $field): void
    {
        // Map 'date' to 'transaction_date' for backwards compatibility
        $dbField = $field === 'date' ? 'transaction_date' : $field;
        
        if ($this->sortField === $dbField) {
            // Toggle direction if same field
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $dbField;
            $this->sortDirection = 'asc';
        }
    }
    
    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'accountFilter',
            'categoryFilter',
            'typeFilter',
            'recurringFilter',
            'dateFrom',
            'dateTo',
            'amountMin',
            'amountMax'
        ]);
        $this->clearSelection();
        $this->resetPage();
    }
    
    /**
     * Bulk delete selected transactions
     */
    public function bulkDelete(): void
    {
        if (empty($this->selectedTransactions)) {
            return;
        }
        
        Transaction::whereIn('id', $this->selectedTransactions)
            ->where('user_id', auth()->id())
            ->delete(); // Soft delete
        
        $count = count($this->selectedTransactions);
        $this->clearSelection();
        $this->resetPage();
        
        session()->flash('message', $count . ' transaction(s) deleted successfully.');
    }
    
    /**
     * Bulk categorize selected transactions
     */
    public function bulkCategorize(string $category): void
    {
        if (empty($this->selectedTransactions)) {
            return;
        }
        
        Transaction::whereIn('id', $this->selectedTransactions)
            ->where('user_id', auth()->id())
            ->update(['user_category' => $category]);
        
        $count = count($this->selectedTransactions);
        $this->clearSelection();
        $this->resetPage();
        
        session()->flash('message', $count . ' transaction(s) categorized successfully.');
    }
    
    /**
     * Open edit category modal for bulk operations
     */
    public function openBulkCategoryModal(): void
    {
        if (empty($this->selectedTransactions)) {
            session()->flash('error', 'Please select at least one transaction.');
            return;
        }
        $this->showEditCategoryModal = true;
    }
    
    /**
     * Open edit category modal for single transaction
     */
    public function openCategoryModal(string $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
        $this->showEditCategoryModal = true;
    }
    
    /**
     * Export selected transactions to CSV
     */
    public function exportSelected()
    {
        if (empty($this->selectedTransactions)) {
            session()->flash('error', 'Please select at least one transaction to export.');
            return;
        }
        
        $transactions = Transaction::whereIn('id', $this->selectedTransactions)
            ->where('user_id', auth()->id())
            ->with(['account'])
            ->orderBy('transaction_date', 'desc')
            ->get();
        
        return $this->exportToCsv($transactions, 'selected-transactions');
    }
    
    /**
     * Export all filtered transactions to CSV
     */
    public function exportAll()
    {
        $transactions = $this->getTransactionsQuery()->get();
        
        return $this->exportToCsv($transactions, 'all-transactions');
    }
    
    /**
     * Generate CSV download
     */
    protected function exportToCsv($transactions, string $filename): StreamedResponse
    {
        $filename = $filename . '-' . date('Y-m-d') . '.csv';
        
        return response()->streamDownload(function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['Date', 'Merchant', 'Description', 'Category', 'Account', 'Amount', 'Type', 'Recurring']);
            
            // Data rows
            foreach ($transactions as $transaction) {
                $category = $transaction->user_category ?? ($transaction->category ?? (is_array($transaction->plaid_categories) ? implode(', ', $transaction->plaid_categories) : 'Uncategorized'));
                $type = $transaction->amount > 0 ? 'Debit' : 'Credit';
                $recurring = $transaction->is_recurring ? ($transaction->recurring_frequency ?? 'Yes') : 'No';
                
                fputcsv($file, [
                    $transaction->transaction_date->format('Y-m-d'),
                    $transaction->merchant_name ?? '',
                    $transaction->name,
                    $category,
                    $transaction->account->account_name ?? 'N/A',
                    number_format(abs($transaction->amount), 2),
                    $type,
                    $recurring,
                ]);
            }
            
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
    
    /**
     * Listen for events from child components
     */
    protected $listeners = [
        'category-updated' => 'handleCategoryUpdated',
        'transaction-created' => 'handleTransactionCreated',
        'close-modal' => 'handleCloseModal',
    ];

    /**
     * Handle category updated event
     */
    public function handleCategoryUpdated(): void
    {
        $this->showEditCategoryModal = false;
        $this->selectedTransactionId = null;
        $this->clearSelection();
    }

    /**
     * Handle transaction created event
     */
    public function handleTransactionCreated(): void
    {
        $this->showCreateTransactionModal = false;
        $this->resetPage();
    }

    /**
     * Handle close modal event
     */
    public function handleCloseModal(): void
    {
        $this->showEditCategoryModal = false;
        $this->showCreateTransactionModal = false;
        $this->selectedTransactionId = null;
    }
    
    /**
     * Show transaction details modal
     */
    public function showDetails(string $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
        $this->showDetailsModal = true;
    }
    
    /**
     * Close details modal
     */
    public function closeDetails(): void
    {
        $this->showDetailsModal = false;
        $this->selectedTransactionId = null;
    }
    
    /**
     * Toggle select all transactions
     */
    public function toggleSelectAll(): void
    {
        $this->selectAll = !$this->selectAll;
        if ($this->selectAll) {
            $this->selectedTransactions = $this->getTransactionsQuery()->pluck('id')->toArray();
        } else {
            $this->selectedTransactions = [];
        }
    }
    
    /**
     * Toggle selection of a single transaction
     */
    public function toggleSelection(string $transactionId): void
    {
        if (in_array($transactionId, $this->selectedTransactions)) {
            $this->selectedTransactions = array_diff($this->selectedTransactions, [$transactionId]);
        } else {
            $this->selectedTransactions[] = $transactionId;
        }
        $this->selectAll = false;
    }
    
    /**
     * Clear all selections
     */
    public function clearSelection(): void
    {
        $this->selectedTransactions = [];
        $this->selectAll = false;
    }
    
    /**
     * Get the query for transactions with filters applied
     */
    protected function getTransactionsQuery(): Builder
    {
        $query = Transaction::query()
            ->where('user_id', auth()->id())
            ->with(['account']);
        
        // Search by merchant name or description
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('merchant_name', 'ilike', '%' . $this->search . '%')
                  ->orWhere('name', 'ilike', '%' . $this->search . '%');
            });
        }
        
        // Filter by account
        if ($this->accountFilter) {
            $query->where('account_id', $this->accountFilter);
        }
        
        // Filter by category
        if ($this->categoryFilter) {
            $query->where(function ($q) {
                $q->where('user_category', $this->categoryFilter)
                  ->orWhere('category', $this->categoryFilter)
                  ->orWhereJsonContains('plaid_categories', $this->categoryFilter);
            });
        }
        
        // Filter by type (debit/credit)
        // Plaid convention: positive amounts = debits (money out), negative = credits (money in)
        if ($this->typeFilter === 'debit') {
            $query->where('amount', '>', 0);
        } elseif ($this->typeFilter === 'credit') {
            $query->where('amount', '<', 0);
        }
        
        // Filter by recurring status
        if ($this->recurringFilter === 'recurring') {
            $query->where('is_recurring', true);
        } elseif ($this->recurringFilter === 'one-time') {
            $query->where('is_recurring', false);
        }
        
        // Filter by date range
        if ($this->dateFrom) {
            $query->where('transaction_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('transaction_date', '<=', $this->dateTo);
        }
        
        // Filter by amount range
        if ($this->amountMin > 0) {
            $query->where('amount', '>=', -$this->amountMin);
        }
        if ($this->amountMax > 0) {
            $query->where('amount', '<=', -$this->amountMax);
        }
        
        // Apply sorting - map 'date' to 'transaction_date' if needed
        $sortField = $this->sortField === 'date' ? 'transaction_date' : $this->sortField;
        $query->orderBy($sortField, $this->sortDirection);
        
        return $query;
    }
    
    /**
     * Calculate summary statistics
     */
    protected function getSummaryStats(): array
    {
        $allTransactions = $this->getTransactionsQuery()->get();
        
        $totalIncome = $allTransactions->where('amount', '>', 0)->sum('amount');
        $totalExpenses = abs($allTransactions->where('amount', '<', 0)->sum('amount'));
        $netChange = $totalIncome - $totalExpenses;
        
        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_change' => $netChange,
            'transaction_count' => $allTransactions->count(),
        ];
    }
    
    /**
     * Get unique categories for filter dropdown
     */
    protected function getCategories(): array
    {
        $transactions = Transaction::where('user_id', auth()->id())
            ->where(function ($q) {
                $q->whereNotNull('user_category')
                  ->orWhereNotNull('category')
                  ->orWhereNotNull('plaid_categories');
            })
            ->get();
        
        $categories = [];
        foreach ($transactions as $transaction) {
            // Add user_category if set
            if ($transaction->user_category && !in_array($transaction->user_category, $categories)) {
                $categories[] = $transaction->user_category;
            }
            
            // Add category if set
            if ($transaction->category && !in_array($transaction->category, $categories)) {
                $categories[] = $transaction->category;
            }
            
            // Add plaid_categories if available
            if ($transaction->plaid_categories && is_array($transaction->plaid_categories)) {
                foreach ($transaction->plaid_categories as $category) {
                    if (!in_array($category, $categories)) {
                        $categories[] = $category;
                    }
                }
            }
        }
        
        sort($categories);
        return $categories;
    }
    
    /**
     * Get user's accounts for filter dropdown
     */
    protected function getAccounts()
    {
        return auth()->user()->accounts()->orderBy('account_name')->get();
    }
    
    /**
     * Get selected transaction for details modal
     */
    protected function getSelectedTransaction(): ?Transaction
    {
        if (!$this->selectedTransactionId) {
            return null;
        }
        
        return Transaction::where('id', $this->selectedTransactionId)
            ->where('user_id', auth()->id())
            ->with(['account'])
            ->first();
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        return view('livewire.transactions.transactions-list', [
            'transactions' => $this->getTransactionsQuery()->paginate($this->perPage),
            'summaryStats' => $this->getSummaryStats(),
            'categories' => $this->getCategories(),
            'accounts' => $this->getAccounts(),
            'selectedTransaction' => $this->getSelectedTransaction(),
        ]);
    }
}


