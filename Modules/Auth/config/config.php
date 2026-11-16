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
];
