<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Transactions Controller
 * 
 * Handles transaction listing and management for authenticated users.
 * Uses Livewire component for interactive features (filtering, sorting, search).
 */
class TransactionsController extends Controller
{
    /**
     * Display a listing of the user's transactions.
     * 
     * The actual data loading and filtering is handled by the TransactionsList Livewire component
     * for real-time interactivity without page reloads.
     */
    public function index(): View
    {
        return view('transactions.index');
    }
}


