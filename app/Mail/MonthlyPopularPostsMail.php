<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyPopularPostsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $posts;

    public $user;

    public $popularUsers;

    /**
     * Create a new message instance.
     */
    public function __construct($posts, $user, $popularUsers)
    {
        $this->posts = $posts;
        $this->user = $user;
        $this->popularUsers = $popularUsers;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔥 O que foi popular no mês de '.ucfirst(Carbon::now()->subMonth()->locale('pt')->translatedFormat('F')),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.monthly_popular_posts',
            with: [
                'mes' => Carbon::now()->subMonth()->locale('pt')->translatedFormat('F'),
                'user' => $this->user,
                'posts' => $this->posts,
                'popularUsers' => $this->popularUsers,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
