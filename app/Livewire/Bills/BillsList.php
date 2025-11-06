<?php

declare(strict_types=1);

namespace App\Livewire\Bills;

use App\Models\Bill;
use App\Models\PaySchedule;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Bills List Livewire Component
 * 
 * Provides comprehensive bill management with filtering, calendar view toggle,
 * payment tracking, and integration with pay schedules.
 */
class BillsList extends Component
{
    use WithPagination;

    // View mode: 'list' or 'calendar'
    public string $viewMode = 'list';
    
    // Filters
    public string $statusFilter = 'all'; // 'all', 'paid', 'unpaid', 'overdue', 'upcoming', 'due'
    public string $categoryFilter = 'all';
    public string $search = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public bool $autopayFilter = false;
    public string $sortBy = 'next_due_date'; // 'next_due_date', 'amount', 'name'
    public string $sortDirection = 'asc';
    
    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showDetailsModal = false;
    public bool $showPaymentHistoryModal = false;
    public ?string $selectedBillId = null;
    
    // Form data for create/edit
    public string $name = '';
    public ?string $description = null;
    public float $amount = 0;
    public string $currency = 'USD';
    public string $due_date = '';
    public string $next_due_date = '';
    public string $frequency = 'monthly'; // 'weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'custom'
    public ?int $frequency_value = null;
    public ?string $category = null;
    public string $payment_status = 'upcoming';
    public bool $is_autopay = false;
    public ?string $autopay_account = null;
    public ?string $payment_link = null;
    public ?string $payee_name = null;
    public bool $reminder_enabled = true;
    public int $reminder_days_before = 3;
    public ?string $notes = null;
    public string $priority = 'medium';
    
    // Bulk actions
    public array $selectedBills = [];
    public bool $selectAll = false;
    
    /**
     * Reset pagination when filters change
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }
    
    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }
    
    public function updatingViewMode(): void
    {
        $this->resetPage();
    }
    
    /**
     * Toggle view mode
     */
    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'list' ? 'calendar' : 'list';
    }
    
    /**
     * Open create modal
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }
    
    /**
     * Open edit modal
     */
    public function edit(string $billId): void
    {
        $bill = Bill::where('id', $billId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $this->selectedBillId = $billId;
        $this->name = $bill->name;
        $this->description = $bill->description;
        $this->amount = abs((float) $bill->amount);
        $this->currency = $bill->currency ?? 'USD';
        $this->due_date = $bill->due_date?->format('Y-m-d') ?? '';
        $this->next_due_date = $bill->next_due_date?->format('Y-m-d') ?? '';
        $this->frequency = $bill->frequency ?? 'monthly';
        $this->frequency_value = $bill->frequency_value;
        $this->category = $bill->category;
        $this->payment_status = $bill->payment_status ?? 'upcoming';
        $this->is_autopay = $bill->is_autopay ?? false;
        $this->autopay_account = $bill->autopay_account;
        $this->payment_link = $bill->payment_link;
        $this->payee_name = $bill->payee_name;
        $this->reminder_enabled = $bill->reminder_enabled ?? true;
        $this->reminder_days_before = $bill->reminder_days_before ?? 3;
        $this->notes = $bill->notes;
        $this->priority = $bill->priority ?? 'medium';
        
        $this->showEditModal = true;
    }
    
    /**
     * Save bill (create or update)
     */
    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'due_date' => 'required|date',
            'next_due_date' => 'required|date',
            'frequency' => 'required|in:weekly,biweekly,monthly,quarterly,annual,custom',
            'frequency_value' => 'nullable|integer|min:1',
            'category' => 'nullable|in:rent,mortgage,utilities,internet,phone,insurance,subscription,loan,credit_card,other',
            'payment_status' => 'required|in:upcoming,due,overdue,paid,scheduled',
            'is_autopay' => 'boolean',
            'autopay_account' => 'nullable|string|max:255',
            'payment_link' => 'nullable|url|max:500',
            'payee_name' => 'nullable|string|max:255',
            'reminder_enabled' => 'boolean',
            'reminder_days_before' => 'required|integer|min:0|max:30',
            'notes' => 'nullable|string|max:2000',
            'priority' => 'required|in:low,medium,high,critical',
        ]);
        
        $data = [
            'user_id' => auth()->id(),
            'name' => $this->name,
            'description' => $this->description,
            'amount' => -abs($this->amount), // Bills are negative amounts
            'currency' => $this->currency,
            'due_date' => $this->due_date,
            'next_due_date' => $this->next_due_date,
            'frequency' => $this->frequency,
            'frequency_value' => $this->frequency_value,
            'category' => $this->category,
            'payment_status' => $this->payment_status,
            'is_autopay' => $this->is_autopay,
            'autopay_account' => $this->autopay_account,
            'payment_link' => $this->payment_link,
            'payee_name' => $this->payee_name,
            'reminder_enabled' => $this->reminder_enabled,
            'reminder_days_before' => $this->reminder_days_before,
            'notes' => $this->notes,
            'priority' => $this->priority,
            'auto_detected' => false,
        ];
        
        if ($this->showEditModal && $this->selectedBillId) {
            // Update existing bill
            Bill::where('id', $this->selectedBillId)
                ->where('user_id', auth()->id())
                ->update($data);
            
            session()->flash('success', 'Bill updated successfully!');
        } else {
            // Create new bill
            Bill::create($data);
            session()->flash('success', 'Bill created successfully!');
        }
        
        $this->closeModal();
        $this->resetForm();
    }
    
    /**
     * Mark bill as paid
     */
    public function markAsPaid(string $billId): void
    {
        $bill = Bill::where('id', $billId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $amount = abs((float) $bill->amount);
        $bill->markAsPaid($amount);
        
        session()->flash('success', 'Bill marked as paid!');
    }
    
    /**
     * Bulk mark bills as paid
     */
    public function bulkMarkAsPaid(): void
    {
        if (empty($this->selectedBills)) {
            return;
        }
        
        $bills = Bill::whereIn('id', $this->selectedBills)
            ->where('user_id', auth()->id())
            ->get();
        
        foreach ($bills as $bill) {
            $amount = abs((float) $bill->amount);
            $bill->markAsPaid($amount);
        }
        
        $this->selectedBills = [];
        $this->selectAll = false;
        
        session()->flash('success', count($bills) . ' bill(s) marked as paid!');
    }
    
    /**
     * Delete bill
     */
    public function delete(string $billId): void
    {
        Bill::where('id', $billId)
            ->where('user_id', auth()->id())
            ->delete();
        
        session()->flash('success', 'Bill deleted successfully!');
        $this->closeModal();
    }
    
    /**
     * Show bill details
     */
    public function showDetails(string $billId): void
    {
        $this->selectedBillId = $billId;
        $this->showDetailsModal = true;
    }
    
    /**
     * Show payment history
     */
    public function showPaymentHistory(string $billId): void
    {
        $this->selectedBillId = $billId;
        $this->showPaymentHistoryModal = true;
    }
    
    /**
     * Close all modals
     */
    public function closeModal(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDetailsModal = false;
        $this->showPaymentHistoryModal = false;
        $this->selectedBillId = null;
    }
    
    /**
     * Reset form fields
     */
    protected function resetForm(): void
    {
        $this->name = '';
        $this->description = null;
        $this->amount = 0;
        $this->currency = 'USD';
        $this->due_date = '';
        $this->next_due_date = '';
        $this->frequency = 'monthly';
        $this->frequency_value = null;
        $this->category = null;
        $this->payment_status = 'upcoming';
        $this->is_autopay = false;
        $this->autopay_account = null;
        $this->payment_link = null;
        $this->payee_name = null;
        $this->reminder_enabled = true;
        $this->reminder_days_before = 3;
        $this->notes = null;
        $this->priority = 'medium';
        $this->selectedBillId = null;
    }
    
    /**
     * Toggle sort direction
     */
    public function sort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    /**
     * Toggle select all bills
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedBills = $this->getBillsQuery()->pluck('id')->toArray();
        } else {
            $this->selectedBills = [];
        }
    }
    
    /**
     * Get bills query with filters
     */
    protected function getBillsQuery(): Builder
    {
        $query = Bill::query()
            ->where('user_id', auth()->id());
        
        // Search by name or description
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'ilike', '%' . $this->search . '%')
                  ->orWhere('description', 'ilike', '%' . $this->search . '%')
                  ->orWhere('payee_name', 'ilike', '%' . $this->search . '%');
            });
        }
        
        // Filter by category
        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }
        
        // Filter by autopay
        if ($this->autopayFilter) {
            $query->where('is_autopay', true);
        }
        
        // Filter by date range
        if ($this->dateFrom) {
            $query->where('next_due_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('next_due_date', '<=', $this->dateTo);
        }
        
        // Filter by status
        $today = now()->toDateString();
        if ($this->statusFilter === 'paid') {
            $query->where('payment_status', 'paid')
                  ->whereNotNull('last_payment_date')
                  ->where('last_payment_date', '>=', now()->startOfMonth()->toDateString());
        } elseif ($this->statusFilter === 'unpaid') {
            $query->whereIn('payment_status', ['upcoming', 'due', 'overdue']);
        } elseif ($this->statusFilter === 'overdue') {
            $query->where(function ($q) use ($today) {
                $q->where('payment_status', 'overdue')
                  ->orWhere(function ($q2) use ($today) {
                      $q2->where('next_due_date', '<', $today)
                         ->whereIn('payment_status', ['upcoming', 'due']);
                  });
            });
        } elseif ($this->statusFilter === 'upcoming') {
            $query->where('payment_status', 'upcoming')
                  ->where('next_due_date', '>=', $today);
        } elseif ($this->statusFilter === 'due') {
            $query->where('payment_status', 'due')
                  ->where('next_due_date', '>=', $today);
        }
        
        // Sorting
        $query->orderBy($this->sortBy, $this->sortDirection);
        
        return $query;
    }
    
    /**
     * Get summary statistics
     */
    protected function getSummaryStats(): array
    {
        $today = now()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();
        
        $allBills = Bill::where('user_id', auth()->id())->get();
        
        // Calculate total due (sum of absolute amounts for unpaid bills)
        $unpaidBills = $allBills->filter(function ($bill) {
            return !in_array($bill->payment_status, ['paid']);
        });
        $totalDue = $unpaidBills->sum(function ($bill) {
            return abs((float) $bill->amount);
        });
        
        // Count paid this month
        $paidThisMonth = $allBills->filter(function ($bill) {
            return $bill->payment_status === 'paid' && 
                   $bill->last_payment_date && 
                   Carbon::parse($bill->last_payment_date)->isCurrentMonth();
        })->count();
        
        // Count upcoming bills
        $upcomingCount = $allBills->filter(function ($bill) use ($endOfMonth) {
            return in_array($bill->payment_status, ['upcoming', 'due']) &&
                   $bill->next_due_date &&
                   $bill->next_due_date->format('Y-m-d') <= $endOfMonth;
        })->count();
        
        // Count overdue bills
        $overdueCount = $allBills->filter(function ($bill) use ($today) {
            return $bill->isOverdue();
        })->count();
        
        return [
            'total_due' => $totalDue,
            'paid_this_month' => $paidThisMonth,
            'upcoming_count' => $upcomingCount,
            'overdue_count' => $overdueCount,
        ];
    }
    
    /**
     * Get selected bill for details
     */
    protected function getSelectedBill(): ?Bill
    {
        if (!$this->selectedBillId) {
            return null;
        }
        
        return Bill::where('id', $this->selectedBillId)
            ->where('user_id', auth()->id())
            ->with(['transactions' => function ($query) {
                $query->orderBy('transaction_date', 'desc');
            }])
            ->first();
    }
    
    /**
     * Get bills due before next payday
     */
    protected function getBillsDueBeforeNextPayday()
    {
        $paySchedule = auth()->user()->activePaySchedule;
        
        if (!$paySchedule) {
            return collect();
        }
        
        $nextPayDate = $paySchedule->next_pay_date ?? $paySchedule->calculateNextPayDate();
        
        return Bill::where('user_id', auth()->id())
            ->where(function ($query) {
                $query->whereIn('payment_status', ['upcoming', 'due', 'overdue'])
                      ->orWhereNull('payment_status');
            })
            ->where('next_due_date', '<=', $nextPayDate)
            ->where('next_due_date', '>=', now()->toDateString())
            ->orderBy('next_due_date')
            ->get();
    }
    
    /**
     * Get pay schedule info
     */
    protected function getPayScheduleInfo(): array
    {
        $paySchedule = auth()->user()->activePaySchedule;
        
        if (!$paySchedule) {
            return [
                'exists' => false,
                'next_pay_date' => null,
                'days_until' => null,
                'net_pay' => null,
            ];
        }
        
        $nextPayDate = $paySchedule->next_pay_date ?? $paySchedule->calculateNextPayDate();
        $billsDueBeforePayday = $this->getBillsDueBeforeNextPayday();
        $totalDueBeforePayday = $billsDueBeforePayday->sum(function ($bill) {
            return abs((float) $bill->amount);
        });
        
        return [
            'exists' => true,
            'next_pay_date' => $nextPayDate,
            'days_until' => now()->diffInDays($nextPayDate, false),
            'net_pay' => $paySchedule->net_pay,
            'total_due_before_payday' => $totalDueBeforePayday,
            'bills_count' => $billsDueBeforePayday->count(),
        ];
    }
    
    /**
     * Render the component
     */
    public function render(): View
    {
        return view('livewire.bills.bills-list', [
            'bills' => $this->getBillsQuery()->paginate(20),
            'summaryStats' => $this->getSummaryStats(),
            'selectedBill' => $this->getSelectedBill(),
            'billsDueBeforePayday' => $this->getBillsDueBeforeNextPayday(),
            'payScheduleInfo' => $this->getPayScheduleInfo(),
        ]);
    }
}
