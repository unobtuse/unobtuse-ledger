<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Jobs\RefreshAllAccountBalances;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Refresh Account Balances On Login Listener
 * 
 * Listens to login events and dispatches a job to refresh
 * all account balances for the authenticated user.
 */
class RefreshAccountBalancesOnLogin implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // Dispatch job to refresh account balances
        RefreshAllAccountBalances::dispatch($event->user);
    }
}


