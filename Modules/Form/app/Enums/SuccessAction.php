<?php

namespace Modules\Form\Enums;

/**
 * Ce que fait l'app une fois un formulaire envoyé avec succès
 * (décision 2026-11-17-formulaires-profil-generiques). L'app ne connaît que ces
 * valeurs : en ajouter une demande une nouvelle version de l'app.
 */
enum SuccessAction: string
{
    /** Retour au profil, qui affiche le message. */
    case PROFILE = 'profile';

    /** Message, puis déconnexion (compte supprimé). */
    case SIGN_OUT = 'sign-out';

    /**
     * Retour à l'écran précédent (une liste), qui affiche le message et se
     * recharge (décision 2026-11-18-adresses-liste-generique).
     */
    case BACK = 'back';
}
