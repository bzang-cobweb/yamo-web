<?php

namespace Modules\Auth\Enums;

/**
 * Ce qu'un code de vérification permet d'achever.
 */
enum VerificationPurpose: string
{
    case LOGIN = 'login';
    case REGISTER = 'register';
    case CHANGE_EMAIL = 'change_email';
    case CHANGE_PHONE = 'change_phone';
    case RESET_PASSWORD = 'reset_password';

    /** Opération sur un compte connecté : le jeton de la requête doit être le sien. */
    public function needsSignedInUser(): bool
    {
        return in_array($this, [self::CHANGE_EMAIL, self::CHANGE_PHONE], true);
    }
}
