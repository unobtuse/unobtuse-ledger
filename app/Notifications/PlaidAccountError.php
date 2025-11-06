<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Plaid Account Error Notification
 * 
 * Sent when a Plaid account encounters an error that requires user attention.
 */
class PlaidAccountError extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Account $account,
        public string $errorCode,
        public string $errorMessage
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

        $mailMessage = (new MailMessage)
            ->subject('Issue with Your Bank Account Connection')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("We encountered an issue syncing your {$institutionName} account ({$accountName}).")
            ->line("**Error:** {$this->errorMessage}");

        // Add resolution steps based on error code
        $resolutionSteps = $this->getResolutionSteps($this->errorCode);
        if (!empty($resolutionSteps)) {
            $mailMessage->line('**What you can do:**')
                ->bulletList($resolutionSteps);
        }

        return $mailMessage
            ->action('View Account Settings', route('accounts.index'))
            ->line('If the issue persists, please contact support for assistance.');
    }

    /**
     * Get resolution steps based on error code.
     *
     * @param string $errorCode
     * @return array<string>
     */
    protected function getResolutionSteps(string $errorCode): array
    {
        return match($errorCode) {
            'ITEM_LOGIN_REQUIRED' => [
                'Log in to your bank account to verify your credentials',
                'Reconnect your account if prompted',
            ],
            'USER_SETUP_REQUIRED' => [
                'Complete any required setup steps with your bank',
                'Reconnect your account once setup is complete',
            ],
            'ITEM_NOT_SUPPORTED' => [
                'This account type may not be supported by Plaid',
                'Contact support if you believe this is an error',
            ],
            'INSTITUTION_DOWN' => [
                'Your bank\'s systems may be temporarily unavailable',
                'Try again later - we\'ll automatically retry',
            ],
            default => [
                'Try reconnecting your account',
                'Contact support if the issue persists',
            ],
        };
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'plaid_account_error',
            'account_id' => $this->account->id,
            'account_name' => $this->account->display_name,
            'institution_name' => $this->account->institution_name,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'message' => "Error syncing {$this->account->institution_name} account: {$this->errorMessage}",
            'action_url' => route('accounts.index'),
        ];
    }
}


