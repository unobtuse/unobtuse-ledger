<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Models\Payment;
use Livewire\Component;
use Illuminate\Contracts\View\View;

/**
 * Payments List Component
 *
 * Displays payment history for the authenticated user.
 */
class PaymentsList extends Component
{
    public string $statusFilter = 'all'; // all, pending, completed, failed
    public ?string $accountFilter = null;

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $status;
    }

    public function filterByAccount(?string $accountId): void
    {
        $this->accountFilter = $accountId;
    }

    protected function getPayments()
    {
        $query = Payment::where('user_id', auth()->id())
            ->with(['account', 'account.institution'])
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->accountFilter) {
            $query->where('account_id', $this->accountFilter);
        }

        return $query->get();
    }

    public function render(): View
    {
        return view('livewire.payments.payments-list', [
            'payments' => $this->getPayments(),
        ]);
    }
}
