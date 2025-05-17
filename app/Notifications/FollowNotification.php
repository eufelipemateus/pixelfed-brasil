<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Profile;

class FollowNotification extends Notification
{
    use Queueable;
    public $profile;

    /**
     * Create a new notification instance.
     */
    public function __construct($origin_profile_id)
    {
        //
        $this->profile =  Profile::find($origin_profile_id);
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
            ->subject('[Notification]   ' . $this->profile->name . ' started following you')
            ->line('**@' . $this->profile->name .'** started following you');
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
