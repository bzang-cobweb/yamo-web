<?php

namespace Modules\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Prévient l'ancien titulaire d'un numéro qu'il a été retiré de son compte inactif,
 * parce qu'un nouveau titulaire l'a prouvé (décision
 * 2026-11-20-restauration-numero-recycle). Dans la langue du compte.
 */
class PhoneReleasedMail extends Mailable
{
    use Queueable;

    /**
     * @param  string  $maskedPhone  numéro masqué (`+41 ••• 67`)
     */
    public function __construct(public readonly string $maskedPhone) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth::phone.released.subject'));
    }

    public function content(): Content
    {
        return new Content(text: 'auth::mail.phone-released');
    }
}
