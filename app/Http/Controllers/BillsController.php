<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Bills Controller
 * 
 * Handles bill management for authenticated users.
 * Uses Livewire components for interactive features (CRUD operations, filtering).
 */
class BillsController extends Controller
{
    /**
     * Display a listing of the user's bills.
     * 
     * The actual data loading and management is handled by Livewire components
     * for real-time interactivity without page reloads.
     */
    public function index(): View
    {
        return view('bills.index');
    }
}


