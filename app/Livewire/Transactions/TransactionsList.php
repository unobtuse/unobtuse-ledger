<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

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
    public string $sortField = 'date';
    public string $sortDirection = 'desc';
    
    // Pagination
    public int $perPage = 25;
    
    // Modal state
    public bool $showDetailsModal = false;
    public ?int $selectedTransactionId = null;
    
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
    
    /**
     * Sort by a specific field
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            // Toggle direction if same field
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
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
            'dateFrom',
            'dateTo',
            'amountMin',
            'amountMax'
        ]);
        $this->resetPage();
    }
    
    /**
     * Show transaction details modal
     */
    public function showDetails(int $transactionId): void
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
            $query->whereJsonContains('categories', $this->categoryFilter);
        }
        
        // Filter by type (debit/credit)
        if ($this->typeFilter === 'debit') {
            $query->where('amount', '<', 0);
        } elseif ($this->typeFilter === 'credit') {
            $query->where('amount', '>', 0);
        }
        
        // Filter by date range
        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
        }
        
        // Filter by amount range
        if ($this->amountMin > 0) {
            $query->where('amount', '>=', -$this->amountMin);
        }
        if ($this->amountMax > 0) {
            $query->where('amount', '<=', -$this->amountMax);
        }
        
        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);
        
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
            ->whereNotNull('categories')
            ->get();
        
        $categories = [];
        foreach ($transactions as $transaction) {
            if (is_array($transaction->categories)) {
                foreach ($transaction->categories as $category) {
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


