<?php

namespace App\Mail;

use App\Models\Kontak;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminKontakMasukMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Kontak $kontak)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pesan Kontak Baru - ' . $this->kontak->subjek,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_kontak_masuk',
            with: [
                'kontak' => $this->kontak,
            ],
        );
    }
}
