<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Plaid Account Requires Re-authentication Notification
 * 
 * Sent when a Plaid account requires user re-authentication
 * (e.g., credentials expired, password changed, etc.)
 */
class PlaidAccountRequiresReauth extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Account $account
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $accountName = $this->account->display_name;
        $institutionName = $this->account->institution_name ?? 'your bank';

        return (new MailMessage)
            ->subject('Action Required: Reconnect Your Bank Account')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your {$institutionName} account ({$accountName}) needs to be reconnected.")
            ->line('This usually happens when:')
            ->bulletList([
                'Your bank password has changed',
                'Your credentials have expired',
                'Your bank requires additional security verification',
            ])
            ->action('Reconnect Account', route('accounts.index'))
            ->line('Once reconnected, your account will resume syncing automatically.')
            ->line('If you have any questions, please contact support.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'plaid_account_requires_reauth',
            'account_id' => $this->account->id,
            'account_name' => $this->account->display_name,
            'institution_name' => $this->account->institution_name,
            'message' => "Your {$this->account->institution_name} account needs to be reconnected.",
            'action_url' => route('accounts.index'),
        ];
    }
}


