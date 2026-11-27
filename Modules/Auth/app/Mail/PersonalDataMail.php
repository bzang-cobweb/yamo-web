<?php

namespace Modules\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * « Mes données » : les données du compte en pièce jointe JSON (décision 2026-11-27-confidentialite).
 */
class PersonalDataMail extends Mailable
{
    use Queueable;

    /**
     * @param  string  $json  les données, en JSON
     */
    public function __construct(public readonly string $json) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth::privacy.data.mail.subject'));
    }

    public function content(): Content
    {
        return new Content(text: 'auth::mail.personal-data');
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->json, 'yamo-donnees.json')->withMime('application/json')];
    }
}
