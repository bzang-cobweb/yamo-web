<?php

namespace Modules\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail du code de vérification, dans la langue de la requête. Envoyé tout de
 * suite (pas de file d'attente : aucun worker ne tourne).
 */
class VerificationCodeMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly string $code, public readonly int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth::verification.mail.subject', ['code' => $this->code]));
    }

    public function content(): Content
    {
        return new Content(text: 'auth::mail.verification-code');
    }
}
