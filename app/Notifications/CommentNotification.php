<?php

namespace App\Notifications;

use App\Models\Profile;
use App\Models\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $actor;

    public $comment;

    public function __construct($actor_id, $comment_id)
    {
        $this->actor = Profile::find($actor_id);
        $this->comment = Status::find($comment_id);
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
        return (new MailMessage)
            ->subject('[Notification] New comment on your post')
            ->line('**@'.$this->actor->username.'** commented on your post')
            ->action('View Comment', $this->comment->url());
    }
}
