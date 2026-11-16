<?php

namespace Modules\Auth\Enums;

/**
 * Canal d'envoi d'un code : l'e-mail ou le téléphone (SMS).
 */
enum VerificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
}
