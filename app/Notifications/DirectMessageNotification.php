<?php

namespace App\Notifications;

use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DirectMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $actor;

    public function __construct($actor_id)
    {
        $this->actor = Profile::find($actor_id);
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function viaQueues(): array
    {
        return ['mail' => 'email'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Message content is intentionally left out of the email for privacy.
        return (new MailMessage)
            ->subject('[Notification] New direct message')
            ->line('**@'.$this->actor->username.'** sent you a direct message')
            ->action('View Messages', url('/account/direct'));
    }
}
