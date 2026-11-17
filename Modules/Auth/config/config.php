<?php

// Fusionnée sous la clé `auth` (config('auth.…')).
return [
    'name' => 'Auth',

    // Codes de vérification (connexion, inscription, changements, mot de passe oublié).
    'verification_codes' => [
        // Durée de validité d'un code, en minutes.
        'minutes' => (int) env('AUTH_CODE_MINUTES', 10),
        // Essais permis avant que le code soit épuisé.
        'max_attempts' => (int) env('AUTH_CODE_MAX_ATTEMPTS', 5),
        // Délai minimal entre deux envois, en secondes.
        'resend_seconds' => (int) env('AUTH_CODE_RESEND_SECONDS', 60),
    ],

    // Canaux de compte : e-mail, téléphone ou les deux ; au moins un.
    'channels' => [
        'email' => (bool) filter_var(env('AUTH_CHANNEL_EMAIL', true), FILTER_VALIDATE_BOOL),
        'phone' => (bool) filter_var(env('AUTH_CHANNEL_PHONE', true), FILTER_VALIDATE_BOOL),
    ],

    // Numéros recyclés : au-delà de ce délai sans activité, un compte ne
    // réinitialise plus son mot de passe par SMS, et son numéro revient au
    // nouveau titulaire qui le prouve.
    'phone' => [
        'inactive_days' => (int) env('AUTH_PHONE_INACTIVE_DAYS', 180),
    ],

    // Suppression de son compte : récupérable pendant ce délai, purgée ensuite.
    'account_deletion' => [
        // Jours pendant lesquels un administrateur peut restaurer le compte.
        'grace_days' => (int) env('AUTH_ACCOUNT_DELETION_GRACE_DAYS', 30),
    ],
];
