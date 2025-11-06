<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Pay Schedules Controller
 * 
 * Handles pay schedule management for authenticated users.
 * Uses Livewire component for interactive configuration.
 */
class PaySchedulesController extends Controller
{
    /**
     * Display pay schedule configuration and management.
     */
    public function index(): View
    {
        return view('pay-schedules.index');
    }
}


