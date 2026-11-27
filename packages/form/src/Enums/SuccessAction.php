<?php

namespace Bza\Form\Enums;

/**
 * What the app does once a form has been sent successfully
 * (decision 2026-11-17-formulaires-profil-generiques). The app only knows these
 * values: adding one requires a new version of the app.
 */
enum SuccessAction: string
{
    /** Back to the profile, which displays the message. */
    case PROFILE = 'profile';

    /** Message, then sign-out (account deleted). */
    case SIGN_OUT = 'sign-out';

    /**
     * Back to the previous screen (a list), which displays the message and
     * reloads (decision 2026-11-18-adresses-liste-generique).
     */
    case BACK = 'back';
}
