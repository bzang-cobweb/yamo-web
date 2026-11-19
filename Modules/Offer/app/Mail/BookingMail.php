<?php

namespace Modules\Offer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Une demande de réservation, sa réponse ou son annulation (décision
 * 2026-11-19-contrat-reservations), dans la langue du destinataire : les textes
 * arrivent déjà traduits.
 */
class BookingMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $body,
        public readonly ?string $note,
        public readonly string $section,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(text: 'offer::mail.booking');
    }
}
