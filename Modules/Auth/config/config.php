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

    // La photo de profil, envoyée depuis l'app ou le back-office (décision 2026-11-24-avatar-photos-offre-conversions).
    'avatar' => [
        // Les types de fichier acceptés, séparés par des virgules.
        'mime_types' => array_values(array_filter(array_map('trim', explode(',', (string) env('AUTH_AVATAR_MIME_TYPES', 'image/jpeg,image/png,image/webp'))))),
        // La taille maximale d'un envoi, en kilo-octets.
        'max_upload_kb' => (int) env('AUTH_AVATAR_MAX_UPLOAD_KB', 5120),
        // La qualité du WebP enregistré, de 1 à 100.
        'quality' => (int) env('AUTH_AVATAR_QUALITY', 80),
        // La photo envoyée est gardée, recadrée en carré et plafonnée à ce côté, en pixels : les conversions en sont
        // tirées (décision 2026-11-24-avatar-list-detail).
        'original_size' => (int) env('AUTH_AVATAR_ORIGINAL_SIZE', 720),
        // Le côté du carré de chaque conversion, en pixels (trois fois l'affichage, pour les écrans haute densité),
        // recadré au centre, en WebP. La plus grande est le minimum à l'envoi (décision 2026-11-24-avatar-list-detail).
        'conversions' => [
            // En petit : qui agit dans une notification, la liste du back-office et sa barre du haut (48 points).
            'list' => (int) env('AUTH_AVATAR_LIST_SIZE', 144),
            // En grand : le profil, la page d'un membre, le formulaire du back-office (96 points).
            'detail' => (int) env('AUTH_AVATAR_DETAIL_SIZE', 288),
        ],
    ],
];
