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
    /** Vérifier l'adresse e-mail actuelle du compte (décision 2026-11-27-verifier-email-telephone). */
    case VERIFY_EMAIL = 'verify_email';
    /** Vérifier le numéro actuel du compte. */
    case VERIFY_PHONE = 'verify_phone';
    /** Confirmer la suppression de son compte (décision 2026-11-27-confidentialite). */
    case DELETE_ACCOUNT = 'delete_account';
    case RESET_PASSWORD = 'reset_password';

    /** Opération sur un compte connecté : le jeton de la requête doit être le sien. */
    public function needsSignedInUser(): bool
    {
        return in_array($this, [self::CHANGE_EMAIL, self::CHANGE_PHONE, self::VERIFY_EMAIL, self::VERIFY_PHONE, self::DELETE_ACCOUNT], true);
    }
}
