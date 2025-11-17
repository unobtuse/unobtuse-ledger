<?php

declare(strict_types=1);

namespace App\Livewire\Interest;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Interest Charges List Livewire Component
 * 
 * Tracks and displays all interest charges across credit accounts.
 */
class InterestChargesList extends Component
{
    use WithPagination;
    
    // Filters
    public string $search = '';
    public string $accountFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    
    // Date range presets
    public string $dateRange = 'all';
    
    // Sorting
    public string $sortField = 'transaction_date';
    public string $sortDirection = 'desc';
    
    // Pagination
    public int $perPage = 25;
    
    // Modal state
    public bool $showDetailsModal = false;
    public ?string $selectedTransactionId = null;
    
    /**
     * Interest charge keywords
     */
    protected array $interestKeywords = [
        'interest charge',
        'finance charge',
        'interest fee',
        'apr charge',
        'interest',
        'finance',
        'interest paid',
        'credit card interest',
        'accrued interest',
    ];
    
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
     * Get interest charges query
     */
    protected function getInterestChargesQuery()
    {
        $query = Transaction::query()
            ->where('user_id', auth()->id())
            ->where('amount', '>', 0) // Debits only
            ->with(['account']);
        
        // Filter to only interest charges
        $query->where(function ($q) {
            foreach ($this->interestKeywords as $keyword) {
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
     * Calculate summary statistics
     */
    protected function getSummaryStats(): array
    {
        $allCharges = $this->getInterestChargesQuery()->get();
        
        $totalInterest = $allCharges->sum('amount');
        $avgCharge = $allCharges->count() > 0 ? $allCharges->avg('amount') : 0;
        
        // Calculate by time period
        $thisMonth = $allCharges->filter(function ($t) {
            return $t->transaction_date->isCurrentMonth();
        })->sum('amount');
        
        $lastMonth = $allCharges->filter(function ($t) {
            return $t->transaction_date->isLastMonth();
        })->sum('amount');
        
        // Group by account
        $byAccount = $allCharges->groupBy('account_id')->map(function ($charges) {
            return [
                'account' => $charges->first()->account,
                'total' => $charges->sum('amount'),
                'count' => $charges->count(),
            ];
        })->sortByDesc('total');
        
        return [
            'total_interest' => $totalInterest,
            'charge_count' => $allCharges->count(),
            'average_charge' => $avgCharge,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'by_account' => $byAccount,
        ];
    }
    
    /**
     * Get user's accounts
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
     * Reset filters
     */
    public function resetFilters(): void
    {
        $this->reset([
            'search',
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
     * Render component
     */
    public function render(): View
    {
        return view('livewire.interest.interest-charges-list', [
            'interestCharges' => $this->getInterestChargesQuery()->paginate($this->perPage),
            'summaryStats' => $this->getSummaryStats(),
            'accounts' => $this->getAccounts(),
            'selectedTransaction' => $this->getSelectedTransaction(),
        ]);
    }
}
