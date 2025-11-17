<?php

declare(strict_types=1);

namespace App\Livewire\Fees;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Fees List Livewire Component
 * 
 * Identifies and displays all fee-related transactions with categorization,
 * filtering, and summary statistics.
 */
class FeesList extends Component
{
    use WithPagination;
    
    // Filters
    public string $search = '';
    public string $feeTypeFilter = 'all';
    public string $accountFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    
    // Date range presets
    public string $dateRange = 'all'; // 'all', 'this_month', 'last_month', 'last_3_months', 'last_6_months', 'this_year'
    
    // Sorting
    public string $sortField = 'transaction_date';
    public string $sortDirection = 'desc';
    
    // Pagination
    public int $perPage = 25;
    
    // Modal state
    public bool $showDetailsModal = false;
    public ?string $selectedTransactionId = null;
    
    /**
     * Fee type keywords for detection
     */
    protected array $feePatterns = [
        'bank_fee' => [
            'keywords' => ['bank fee', 'service fee', 'monthly fee', 'maintenance fee', 'account fee'],
            'label' => 'Bank Fees',
            'icon' => '🏦',
        ],
        'overdraft' => [
            'keywords' => ['overdraft', 'nsf', 'insufficient funds', 'returned item'],
            'label' => 'Overdraft Fees',
            'icon' => '⚠️',
        ],
        'atm_fee' => [
            'keywords' => ['atm fee', 'atm charge', 'withdrawal fee', 'atm withdrawal'],
            'label' => 'ATM Fees',
            'icon' => '🏧',
        ],
        'late_fee' => [
            'keywords' => ['late fee', 'late charge', 'late payment', 'penalty'],
            'label' => 'Late Fees',
            'icon' => '⏰',
        ],
        'foreign_transaction' => [
            'keywords' => ['foreign transaction', 'international fee', 'currency conversion', 'fx fee'],
            'label' => 'Foreign Transaction Fees',
            'icon' => '🌍',
        ],
        'wire_transfer' => [
            'keywords' => ['wire fee', 'wire transfer fee', 'transfer fee'],
            'label' => 'Wire Transfer Fees',
            'icon' => '💸',
        ],
        'credit_card_fee' => [
            'keywords' => ['annual fee', 'card fee', 'credit card fee', 'membership fee'],
            'label' => 'Credit Card Fees',
            'icon' => '💳',
        ],
        'interest_charge' => [
            'keywords' => ['interest charge', 'finance charge', 'interest fee', 'apr charge'],
            'label' => 'Interest Charges',
            'icon' => '📈',
        ],
        'subscription_fee' => [
            'keywords' => ['subscription fee', 'membership fee', 'monthly charge'],
            'label' => 'Subscription Fees',
            'icon' => '📱',
        ],
        'processing_fee' => [
            'keywords' => ['processing fee', 'transaction fee', 'convenience fee', 'service charge'],
            'label' => 'Processing Fees',
            'icon' => '⚙️',
        ],
        'other_fee' => [
            'keywords' => ['fee', 'charge'],
            'label' => 'Other Fees',
            'icon' => '💰',
        ],
    ];
    
    /**
     * Reset pagination when filters change
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    
    public function updatingFeeTypeFilter(): void
    {
        $this->resetPage();
    }
    
    public function updatingAccountFilter(): void
    {
        $this->resetPage();
    }
    
    public function updatingDateRange(): void
    {
        $this->applyDateRangePreset();
        $this->resetPage();
    }
    
    /**
     * Apply date range preset
     */
    protected function applyDateRangePreset(): void
    {
        match($this->dateRange) {
            'this_month' => [
                $this->dateFrom = now()->startOfMonth()->format('Y-m-d'),
                $this->dateTo = now()->endOfMonth()->format('Y-m-d'),
            ],
            'last_month' => [
                $this->dateFrom = now()->subMonth()->startOfMonth()->format('Y-m-d'),
                $this->dateTo = now()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            'last_3_months' => [
                $this->dateFrom = now()->subMonths(3)->startOfMonth()->format('Y-m-d'),
                $this->dateTo = now()->endOfMonth()->format('Y-m-d'),
            ],
            'last_6_months' => [
                $this->dateFrom = now()->subMonths(6)->startOfMonth()->format('Y-m-d'),
                $this->dateTo = now()->endOfMonth()->format('Y-m-d'),
            ],
            'this_year' => [
                $this->dateFrom = now()->startOfYear()->format('Y-m-d'),
                $this->dateTo = now()->endOfYear()->format('Y-m-d'),
            ],
            'all' => [
                $this->dateFrom = '',
                $this->dateTo = '',
            ],
            default => null,
        };
    }
    
    /**
     * Detect fee type based on transaction name and category
     */
    protected function detectFeeType(Transaction $transaction): string
    {
        $searchText = strtolower($transaction->name . ' ' . $transaction->merchant_name . ' ' . $transaction->category);
        
        // Check each fee pattern
        foreach ($this->feePatterns as $type => $pattern) {
            foreach ($pattern['keywords'] as $keyword) {
                if (stripos($searchText, $keyword) !== false) {
                    return $type;
                }
            }
        }
        
        return 'other_fee';
    }
    
    /**
     * Check if transaction is likely a fee
     */
    protected function isFeeTransaction(Transaction $transaction): bool
    {
        $searchText = strtolower($transaction->name . ' ' . $transaction->merchant_name . ' ' . $transaction->category);
        
        // Common fee indicators
        $feeIndicators = [
            'fee', 'charge', 'interest', 'penalty', 'overdraft', 'nsf',
            'atm', 'foreign transaction', 'wire', 'transfer fee',
            'maintenance', 'service fee', 'annual fee', 'late fee',
            'processing fee', 'convenience fee', 'finance charge'
        ];
        
        foreach ($feeIndicators as $indicator) {
            if (stripos($searchText, $indicator) !== false) {
                return true;
            }
        }
        
        // Check category
        if ($transaction->category) {
            $categoryLower = strtolower($transaction->category);
            if (stripos($categoryLower, 'fee') !== false || 
                stripos($categoryLower, 'charge') !== false ||
                stripos($categoryLower, 'interest') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get fees query with filters applied
     */
    protected function getFeesQuery()
    {
        $query = Transaction::query()
            ->where('user_id', auth()->id())
            ->where('amount', '>', 0) // Debits only (money out)
            ->with(['account']);
        
        // Filter to only fee transactions
        $query->where(function ($q) {
            foreach (array_merge(...array_column($this->feePatterns, 'keywords')) as $keyword) {
                $q->orWhereRaw("LOWER(name) LIKE ?", ['%' . strtolower($keyword) . '%'])
                  ->orWhereRaw("LOWER(merchant_name) LIKE ?", ['%' . strtolower($keyword) . '%'])
                  ->orWhereRaw("LOWER(category) LIKE ?", ['%' . strtolower($keyword) . '%']);
            }
        });
        
        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'ilike', '%' . $this->search . '%')
                  ->orWhere('merchant_name', 'ilike', '%' . $this->search . '%')
                  ->orWhere('category', 'ilike', '%' . $this->search . '%');
            });
        }
        
        // Account filter
        if ($this->accountFilter) {
            $query->where('account_id', $this->accountFilter);
        }
        
        // Date range filter
        if ($this->dateFrom) {
            $query->where('transaction_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('transaction_date', '<=', $this->dateTo);
        }
        
        // Sorting
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query;
    }
    
    /**
     * Get fees grouped by type
     */
    protected function getFeesByType(): Collection
    {
        $allFees = $this->getFeesQuery()->get();
        
        // Group by fee type
        $grouped = collect();
        
        foreach ($allFees as $transaction) {
            $feeType = $this->detectFeeType($transaction);
            
            if ($this->feeTypeFilter !== 'all' && $feeType !== $this->feeTypeFilter) {
                continue;
            }
            
            if (!$grouped->has($feeType)) {
                $grouped->put($feeType, [
                    'type' => $feeType,
                    'label' => $this->feePatterns[$feeType]['label'] ?? 'Other Fees',
                    'icon' => $this->feePatterns[$feeType]['icon'] ?? '💰',
                    'transactions' => collect(),
                    'total' => 0,
                    'count' => 0,
                ]);
            }
            
            // Get the group, modify it, and put it back
            $group = $grouped->get($feeType);
            $group['transactions']->push($transaction);
            $group['total'] += (float) $transaction->amount;
            $group['count']++;
            $grouped->put($feeType, $group);
        }
        
        return $grouped->sortByDesc('total');
    }
    
    /**
     * Calculate summary statistics
     */
    protected function getSummaryStats(): array
    {
        $allFees = $this->getFeesQuery()->get();
        
        $totalFees = $allFees->sum('amount');
        $avgFee = $allFees->count() > 0 ? $allFees->avg('amount') : 0;
        
        // Calculate fees by time period
        $thisMonth = $allFees->filter(function ($t) {
            return $t->transaction_date->isCurrentMonth();
        })->sum('amount');
        
        $lastMonth = $allFees->filter(function ($t) {
            return $t->transaction_date->isLastMonth();
        })->sum('amount');
        
        return [
            'total_fees' => $totalFees,
            'fee_count' => $allFees->count(),
            'average_fee' => $avgFee,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'fee_types_count' => $this->getFeesByType()->count(),
        ];
    }
    
    /**
     * Get user's accounts for filter dropdown
     */
    protected function getAccounts()
    {
        return auth()->user()->accounts()->orderBy('account_name')->get();
    }
    
    /**
     * Show details modal
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
     * Get selected transaction
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
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'feeTypeFilter',
            'accountFilter',
            'dateFrom',
            'dateTo',
            'dateRange'
        ]);
        $this->resetPage();
    }
    
    /**
     * Sort by field
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        return view('livewire.fees.fees-list', [
            'feesByType' => $this->getFeesByType(),
            'allFees' => $this->getFeesQuery()->paginate($this->perPage),
            'summaryStats' => $this->getSummaryStats(),
            'accounts' => $this->getAccounts(),
            'feePatterns' => $this->feePatterns,
            'selectedTransaction' => $this->getSelectedTransaction(),
        ]);
    }
}
