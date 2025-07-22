<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WeeklyPopularPostsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public $posts, public $user, public $promoters = [])
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Publicações  Populares da Semana no ' . config('app.name'),
            replyTo: [config('instance.email')]
        );
    }


    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html:'emails.weekly_popular_posts',
            with: [
                'user' => $this->user,
                'posts' => $this->posts,
                'promoters' => $this->promoters
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
