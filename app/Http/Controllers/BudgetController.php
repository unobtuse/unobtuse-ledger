<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Budget Controller
 * 
 * Handles budget overview and management for authenticated users.
 * Uses Livewire component for interactive dashboard and calculations.
 */
class BudgetController extends Controller
{
    /**
     * Display budget overview dashboard.
     */
    public function index(): View
    {
        return view('budget.index');
    }
}


