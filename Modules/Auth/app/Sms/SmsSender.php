<?php

namespace Modules\Auth\Sms;

/**
 * Envoi d'un SMS. Interchangeable : en local, `LogSmsSender` écrit dans le journal ;
 * un fournisseur réel (Twilio, Vonage…) se branche plus tard dans
 * `AuthServiceProvider` (décision 2026-11-17-contrat-pays-telephone-codes).
 */
interface SmsSender
{
    /**
     * @param  string  $to  numéro E.164
     */
    public function send(string $to, string $message): void;
}
