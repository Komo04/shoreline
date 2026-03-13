<?php

namespace App\Mail;

use App\Models\Kontak;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class KontakReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Kontak $kontak,
        public string $emailSubject,
        public string $messageText
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.kontak_reply',
            with: [
                'kontak' => $this->kontak,
                'messageText' => $this->messageText,
            ],
        );
    }
}
