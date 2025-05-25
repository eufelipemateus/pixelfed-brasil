<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Profile;
use App\Status;

class LikeNotification extends Notification
{
    use Queueable;
    public $actor;
    public $status;

    /**
     * Create a new notification instance.
     */
    public function __construct($actor_id, $status_id)
    {
        //
        $this->actor =  Profile::find($actor_id);
        $this->status =  Status::find($status_id);

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Notification] Your post was liked!')
            ->line('**@' . $this->actor->username . '** liked your post')
            ->action('View Post', $this->status->url());
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
