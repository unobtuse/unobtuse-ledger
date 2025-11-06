<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Models\Transaction;
use Livewire\Component;

/**
 * Edit Category Livewire Component
 * 
 * Allows users to edit categories for single or multiple transactions.
 */
class EditCategory extends Component
{
    public ?string $selectedTransactionId = null;
    public array $selectedTransactionIds = [];
    public string $category = '';
    public string $customCategory = '';
    public bool $useCustomCategory = false;
    public array $existingCategories = [];

    /**
     * Mount the component
     */
    public function mount(?string $transactionId = null, array $transactionIds = []): void
    {
        $this->selectedTransactionId = $transactionId;
        $this->selectedTransactionIds = !empty($transactionIds) ? $transactionIds : [];
        $this->loadExistingCategories();
        
        // If editing a single transaction, load its current category
        if ($transactionId && empty($transactionIds)) {
            $transaction = Transaction::where('id', $transactionId)
                ->where('user_id', auth()->id())
                ->first();
            
            if ($transaction && $transaction->user_category) {
                $this->category = $transaction->user_category;
            }
        }
    }

    /**
     * Load existing categories from user's transactions
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
     * Save the category
     */
    public function save(): void
    {
        $categoryToSave = $this->useCustomCategory ? $this->customCategory : $this->category;
        
        if (empty($categoryToSave)) {
            session()->flash('error', 'Please select or enter a category.');
            return;
        }

        $transactionIds = [];
        if ($this->selectedTransactionId) {
            $transactionIds[] = $this->selectedTransactionId;
        }
        if (!empty($this->selectedTransactionIds)) {
            $transactionIds = array_merge($transactionIds, $this->selectedTransactionIds);
        }

        if (empty($transactionIds)) {
            session()->flash('error', 'No transactions selected.');
            return;
        }

        Transaction::whereIn('id', $transactionIds)
            ->where('user_id', auth()->id())
            ->update(['user_category' => $categoryToSave]);

        $count = count($transactionIds);
        session()->flash('message', $count . ' transaction(s) categorized successfully.');
        
        $this->dispatch('category-updated');
        $this->dispatch('close-modal');
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
        return view('livewire.transactions.edit-category-modal');
    }
}

