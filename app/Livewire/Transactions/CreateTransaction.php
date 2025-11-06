<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

/**
 * Create Transaction Livewire Component
 * 
 * Allows users to manually create transactions.
 */
class CreateTransaction extends Component
{
    public string $accountId = '';
    public string $merchantName = '';
    public string $description = '';
    public string $amount = '';
    public string $transactionDate = '';
    public string $category = '';
    public string $customCategory = '';
    public bool $useCustomCategory = false;
    public bool $isRecurring = false;
    public string $recurringFrequency = 'monthly';
    public string $notes = '';
    public array $existingCategories = [];

    /**
     * Mount the component
     */
    public function mount(): void
    {
        $this->transactionDate = date('Y-m-d');
        $this->loadExistingCategories();
    }

    /**
     * Load existing categories
     */
    protected function loadExistingCategories(): void
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
            if ($transaction->user_category && !in_array($transaction->user_category, $categories)) {
                $categories[] = $transaction->user_category;
            }
            if ($transaction->category && !in_array($transaction->category, $categories)) {
                $categories[] = $transaction->category;
            }
            if ($transaction->plaid_categories && is_array($transaction->plaid_categories)) {
                foreach ($transaction->plaid_categories as $cat) {
                    if (!in_array($cat, $categories)) {
                        $categories[] = $cat;
                    }
                }
            }
        }
        
        sort($categories);
        $this->existingCategories = $categories;
    }

    /**
     * Get user's accounts
     */
    public function getAccountsProperty()
    {
        return auth()->user()->accounts()->orderBy('account_name')->get();
    }

    /**
     * Save the transaction
     */
    public function save(): void
    {
        $categoryToSave = $this->useCustomCategory ? $this->customCategory : $this->category;
        
        $validated = $this->validate([
            'accountId' => ['required', 'exists:accounts,id'],
            'merchantName' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transactionDate' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['nullable', 'string', 'max:255'],
            'customCategory' => ['nullable', 'string', 'max:255'],
            'isRecurring' => ['boolean'],
            'recurringFrequency' => ['required_if:isRecurring,true', 'in:daily,weekly,biweekly,monthly,quarterly,annual'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'accountId.required' => 'Please select an account.',
            'accountId.exists' => 'The selected account is invalid.',
            'merchantName.required' => 'Merchant name is required.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least $0.01.',
            'transactionDate.required' => 'Transaction date is required.',
            'transactionDate.before_or_equal' => 'Transaction date cannot be in the future.',
        ]);

        // Verify account belongs to user
        $account = Account::where('id', $validated['accountId'])
            ->where('user_id', auth()->id())
            ->first();

        if (!$account) {
            session()->flash('error', 'Invalid account selected.');
            return;
        }

        // Determine transaction type: positive = debit (expense), negative = credit (income)
        // For manual entry, we'll assume positive amounts are expenses (debits)
        // User can enter negative amounts for income
        $amountValue = (float) $validated['amount'];
        $transactionType = $amountValue >= 0 ? 'debit' : 'credit';

        Transaction::create([
            'user_id' => auth()->id(),
            'account_id' => $validated['accountId'],
            'name' => $validated['description'] ?: $validated['merchantName'],
            'merchant_name' => $validated['merchantName'],
            'amount' => $amountValue,
            'iso_currency_code' => $account->iso_currency_code ?? 'USD',
            'transaction_date' => $validated['transactionDate'],
            'transaction_type' => $transactionType,
            'user_category' => $categoryToSave ?: null,
            'is_recurring' => $validated['isRecurring'] ?? false,
            'recurring_frequency' => ($validated['isRecurring'] ?? false) ? $validated['recurringFrequency'] : null,
            'user_notes' => $validated['notes'] ?? null,
            'pending' => false,
        ]);

        session()->flash('message', 'Transaction created successfully.');
        
        $this->dispatch('transaction-created');
        $this->dispatch('close-modal');
        
        // Reset form
        $this->reset([
            'accountId',
            'merchantName',
            'description',
            'amount',
            'category',
            'customCategory',
            'useCustomCategory',
            'isRecurring',
            'recurringFrequency',
            'notes',
        ]);
        $this->transactionDate = date('Y-m-d');
    }

    /**
     * Close the modal
     */
    public function close(): void
    {
        $this->dispatch('close-modal');
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.transactions.create-transaction-modal');
    }
}


