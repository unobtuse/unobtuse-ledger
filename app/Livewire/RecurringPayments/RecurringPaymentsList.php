<?php

declare(strict_types=1);

namespace App\Livewire\RecurringPayments;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Recurring Payments List Livewire Component
 * 
 * Analyzes transaction patterns to identify and display recurring payments.
 * Groups similar transactions by merchant, amount, and frequency.
 */
class RecurringPaymentsList extends Component
{
    // Filters
    public string $frequencyFilter = 'all'; // 'all', 'monthly', 'weekly', 'biweekly', 'yearly'
    public string $statusFilter = 'all'; // 'all', 'active', 'inactive'
    public string $search = '';
    
    // Detection settings
    public int $minOccurrences = 3; // Minimum transactions to be considered recurring
    public int $lookbackMonths = 6; // How many months to analyze
    public float $amountTolerance = 5.0; // Dollar amount variance allowed (±$5)
    
    // Modal state
    public bool $showDetailsModal = false;
    public ?string $selectedGroupId = null;
    
    /**
     * Detect recurring payment patterns
     * 
     * Groups transactions by similar merchant names and amounts,
     * then analyzes frequency patterns.
     */
    protected function detectRecurringPatterns(): Collection
    {
        $startDate = now()->subMonths($this->lookbackMonths);
        
        // Get all debit transactions (money out) within the lookback period
        $transactions = Transaction::where('user_id', auth()->id())
            ->where('transaction_date', '>=', $startDate)
            ->where('amount', '>', 0) // Debits only
            ->orderBy('transaction_date', 'asc')
            ->get();
        
        // Group by normalized merchant name
        $groups = [];
        
        foreach ($transactions as $transaction) {
            $merchantName = $this->normalizeMerchantName($transaction->merchant_name ?? $transaction->name);
            $amount = (float) $transaction->amount;
            
            // Find or create group
            $groupKey = $this->findMatchingGroup($groups, $merchantName, $amount);
            
            if ($groupKey === null) {
                // Create new group
                $groupKey = uniqid('recurring_', true);
                $groups[$groupKey] = [
                    'id' => $groupKey,
                    'merchant_name' => $merchantName,
                    'display_name' => $transaction->merchant_name ?? $transaction->name,
                    'average_amount' => $amount,
                    'min_amount' => $amount,
                    'max_amount' => $amount,
                    'transactions' => [],
                    'frequency' => null,
                    'next_expected_date' => null,
                    'is_active' => true,
                    'category' => $transaction->user_category ?? $transaction->category ?? 'Uncategorized',
                ];
            }
            
            $groups[$groupKey]['transactions'][] = $transaction;
        }
        
        // Analyze each group
        $recurringGroups = collect();
        
        foreach ($groups as $groupKey => $group) {
            // Must have minimum occurrences
            if (count($group['transactions']) < $this->minOccurrences) {
                continue;
            }
            
            // Calculate statistics
            $amounts = collect($group['transactions'])->pluck('amount');
            $group['average_amount'] = $amounts->avg();
            $group['min_amount'] = $amounts->min();
            $group['max_amount'] = $amounts->max();
            $group['total_paid'] = $amounts->sum();
            $group['occurrence_count'] = count($group['transactions']);
            
            // Analyze frequency
            $frequency = $this->analyzeFrequency($group['transactions']);
            $group['frequency'] = $frequency['type'];
            $group['average_days_between'] = $frequency['average_days'];
            
            // Predict next payment
            $lastTransaction = collect($group['transactions'])->last();
            if ($frequency['average_days']) {
                $group['next_expected_date'] = $lastTransaction->transaction_date
                    ->addDays($frequency['average_days']);
                    
                // Check if active (last payment was within expected frequency + buffer)
                $daysSinceLastPayment = now()->diffInDays($lastTransaction->transaction_date, false);
                $expectedWithBuffer = $frequency['average_days'] * 1.5; // 50% buffer
                $group['is_active'] = $daysSinceLastPayment < $expectedWithBuffer;
            }
            
            $group['last_payment_date'] = $lastTransaction->transaction_date;
            $group['days_since_last'] = now()->diffInDays($lastTransaction->transaction_date);
            
            $recurringGroups->push($group);
        }
        
        // Apply filters
        $recurringGroups = $this->applyFilters($recurringGroups);
        
        // Sort by next expected date (soonest first)
        return $recurringGroups->sortBy(function ($group) {
            if ($group['next_expected_date']) {
                return $group['next_expected_date']->timestamp;
            }
            return PHP_INT_MAX;
        });
    }
    
    /**
     * Normalize merchant name for matching
     */
    protected function normalizeMerchantName(string $name): string
    {
        $name = strtolower($name);
        
        // Remove common payment identifiers
        $patterns = [
            '/payment.*/',
            '/\*\*\*.*/',
            '/\d{4,}/', // Remove long numbers
            '/autopay.*/',
            '/recurring.*/',
            '/subscription.*/',
            '/\bpmt\b/',
            '/\bpayment\b/',
            '/\bto\b/',
            '/\bfrom\b/',
        ];
        
        foreach ($patterns as $pattern) {
            $name = preg_replace($pattern, '', $name);
        }
        
        // Remove extra spaces and trim
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        
        return $name;
    }
    
    /**
     * Find matching group for a transaction
     */
    protected function findMatchingGroup(array $groups, string $merchantName, float $amount): ?string
    {
        foreach ($groups as $groupKey => $group) {
            // Check merchant name similarity
            $similarity = 0;
            similar_text($merchantName, $group['merchant_name'], $similarity);
            
            if ($similarity < 80) {
                continue; // Not similar enough
            }
            
            // Check amount is within tolerance
            $avgAmount = $group['average_amount'];
            $amountDiff = abs($amount - $avgAmount);
            
            if ($amountDiff > $this->amountTolerance) {
                continue; // Amount too different
            }
            
            return $groupKey;
        }
        
        return null;
    }
    
    /**
     * Analyze transaction frequency pattern
     */
    protected function analyzeFrequency(array $transactions): array
    {
        if (count($transactions) < 2) {
            return ['type' => 'Unknown', 'average_days' => null];
        }
        
        // Calculate days between each transaction
        $intervals = [];
        for ($i = 1; $i < count($transactions); $i++) {
            $prevDate = $transactions[$i - 1]->transaction_date;
            $currDate = $transactions[$i]->transaction_date;
            $intervals[] = $prevDate->diffInDays($currDate);
        }
        
        $averageDays = array_sum($intervals) / count($intervals);
        
        // Classify frequency
        $type = 'Unknown';
        
        if ($averageDays >= 6 && $averageDays <= 8) {
            $type = 'Weekly';
        } elseif ($averageDays >= 13 && $averageDays <= 15) {
            $type = 'Bi-Weekly';
        } elseif ($averageDays >= 27 && $averageDays <= 33) {
            $type = 'Monthly';
        } elseif ($averageDays >= 85 && $averageDays <= 95) {
            $type = 'Quarterly';
        } elseif ($averageDays >= 360 && $averageDays <= 370) {
            $type = 'Yearly';
        } elseif ($averageDays > 0) {
            $type = 'Every ' . round($averageDays) . ' days';
        }
        
        return [
            'type' => $type,
            'average_days' => round($averageDays),
        ];
    }
    
    /**
     * Apply filters to recurring groups
     */
    protected function applyFilters(Collection $groups): Collection
    {
        // Search filter
        if ($this->search) {
            $groups = $groups->filter(function ($group) {
                return stripos($group['display_name'], $this->search) !== false ||
                       stripos($group['merchant_name'], $this->search) !== false ||
                       stripos($group['category'], $this->search) !== false;
            });
        }
        
        // Frequency filter
        if ($this->frequencyFilter !== 'all') {
            $groups = $groups->filter(function ($group) {
                $frequency = strtolower($group['frequency'] ?? '');
                $filter = strtolower($this->frequencyFilter);
                
                if ($filter === 'monthly') {
                    return strpos($frequency, 'monthly') !== false;
                } elseif ($filter === 'weekly') {
                    return strpos($frequency, 'weekly') !== false;
                } elseif ($filter === 'biweekly') {
                    return strpos($frequency, 'bi-weekly') !== false;
                } elseif ($filter === 'yearly') {
                    return strpos($frequency, 'yearly') !== false;
                } elseif ($filter === 'quarterly') {
                    return strpos($frequency, 'quarterly') !== false;
                }
                
                return true;
            });
        }
        
        // Status filter
        if ($this->statusFilter === 'active') {
            $groups = $groups->filter(fn($group) => $group['is_active']);
        } elseif ($this->statusFilter === 'inactive') {
            $groups = $groups->filter(fn($group) => !$group['is_active']);
        }
        
        return $groups;
    }
    
    /**
     * Show details modal for a recurring group
     */
    public function showDetails(string $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->showDetailsModal = true;
    }
    
    /**
     * Close details modal
     */
    public function closeDetails(): void
    {
        $this->showDetailsModal = false;
        $this->selectedGroupId = null;
    }
    
    /**
     * Get selected recurring group details
     */
    protected function getSelectedGroup()
    {
        if (!$this->selectedGroupId) {
            return null;
        }
        
        $groups = $this->detectRecurringPatterns();
        return $groups->firstWhere('id', $this->selectedGroupId);
    }
    
    /**
     * Calculate summary statistics
     */
    protected function getSummaryStats(Collection $groups): array
    {
        $activeGroups = $groups->filter(fn($g) => $g['is_active']);
        $totalMonthlyEstimate = $activeGroups->sum(function ($group) {
            $avgDays = $group['average_days_between'] ?? 30;
            $paymentsPerMonth = 30 / $avgDays;
            return $group['average_amount'] * $paymentsPerMonth;
        });
        
        return [
            'total_recurring' => $groups->count(),
            'active_recurring' => $activeGroups->count(),
            'inactive_recurring' => $groups->count() - $activeGroups->count(),
            'estimated_monthly' => $totalMonthlyEstimate,
            'total_paid_6mo' => $groups->sum('total_paid'),
        ];
    }
    
    /**
     * Reset filters
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'frequencyFilter', 'statusFilter']);
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        $recurringGroups = $this->detectRecurringPatterns();
        
        return view('livewire.recurring-payments.recurring-payments-list', [
            'recurringGroups' => $recurringGroups,
            'summaryStats' => $this->getSummaryStats($recurringGroups),
            'selectedGroup' => $this->getSelectedGroup(),
        ]);
    }
}
