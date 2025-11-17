<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Models\Account;
use App\Services\PaymentsService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

/**
 * Create Payment Component
 *
 * Allows users to initiate payments through Teller API.
 */
class CreatePayment extends Component
{
    public ?string $selectedAccountId = null;
    public string $recipientName = '';
    public string $recipientAccountNumber = '';
    public string $recipientRoutingNumber = '';
    public string $amount = '';
    public string $memo = '';
    public ?string $scheduledDate = null;
    public bool $isRecurring = false;
    public string $recurrenceFrequency = 'monthly';
    public ?string $recurrenceEndDate = null;
    
    public bool $showModal = false;
    public ?string $error = null;
    public ?string $success = null;

    protected PaymentsService $paymentsService;

    public function boot(PaymentsService $paymentsService): void
    {
        $this->paymentsService = $paymentsService;
    }

    public function openModal(?string $accountId = null): void
    {
        $this->selectedAccountId = $accountId;
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->recipientName = '';
        $this->recipientAccountNumber = '';
        $this->recipientRoutingNumber = '';
        $this->amount = '';
        $this->memo = '';
        $this->scheduledDate = null;
        $this->isRecurring = false;
        $this->recurrenceFrequency = 'monthly';
        $this->recurrenceEndDate = null;
        $this->error = null;
        $this->success = null;
    }

    public function createPayment(): void
    {
        $this->validate([
            'selectedAccountId' => 'required|exists:accounts,id',
            'recipientName' => 'required|string|max:255',
            'recipientAccountNumber' => 'required|string|min:4|max:17',
            'recipientRoutingNumber' => 'required|string|size:9',
            'amount' => 'required|numeric|min:0.01',
            'memo' => 'nullable|string|max:500',
            'scheduledDate' => 'nullable|date|after_or_equal:today',
            'recurrenceEndDate' => 'nullable|date|after:scheduledDate',
        ]);

        try {
            $account = Account::where('id', $this->selectedAccountId)
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->firstOrFail();

            if (!$account->teller_token || !$account->teller_account_id) {
                throw new \Exception('Account is not properly connected to Teller');
            }

            $paymentData = [
                'amount' => (float) $this->amount,
                'recipient_name' => $this->recipientName,
                'recipient_account_number' => $this->recipientAccountNumber,
                'recipient_routing_number' => $this->recipientRoutingNumber,
                'memo' => $this->memo ?: null,
            ];

            if ($this->scheduledDate) {
                $paymentData['scheduled_date'] = $this->scheduledDate;
            }

            $result = $this->paymentsService->createPayment($account, $paymentData);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to create payment');
            }

            // Create payment record in database
            $payment = \App\Models\Payment::create([
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'teller_payment_id' => $result['teller_payment_id'],
                'recipient_name' => $this->recipientName,
                'recipient_account_number' => substr($this->recipientAccountNumber, -4), // Store last 4 only
                'recipient_routing_number' => $this->recipientRoutingNumber,
                'payment_type' => 'ach',
                'payment_method' => $this->isRecurring ? 'recurring' : 'one_time',
                'amount' => (float) $this->amount,
                'currency' => $account->currency ?? 'USD',
                'status' => $result['payment']['status'] ?? 'pending',
                'scheduled_date' => $this->scheduledDate ? \Carbon\Carbon::parse($this->scheduledDate) : null,
                'recurrence_frequency' => $this->isRecurring ? $this->recurrenceFrequency : null,
                'recurrence_end_date' => $this->recurrenceEndDate ? \Carbon\Carbon::parse($this->recurrenceEndDate) : null,
                'memo' => $this->memo ?: null,
                'metadata' => $result['payment'] ?? [],
            ]);

            $this->success = 'Payment created successfully!';
            $this->dispatch('paymentCreated', $payment->id);
            
            // Close modal after a brief delay
            $this->dispatch('closeModal');
            
            Log::info('Payment created', [
                'payment_id' => $payment->id,
                'user_id' => auth()->id(),
                'account_id' => $account->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->error = 'Failed to create payment: ' . $e->getMessage();
        }
    }

    public function getAccountsProperty()
    {
        return Account::where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('institution_name')
            ->orderBy('account_name')
            ->get();
    }

    public function render()
    {
        return view('livewire.payments.create-payment', [
            'accounts' => $this->accounts,
        ]);
    }
}
