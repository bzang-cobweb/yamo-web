<?php

/*
 * Réglages du paquet de formulaires `bza/laravel-form` pour cette application (décision 2026-11-27-paquet-form-acces) :
 * le paquet n'impose rien, l'application choisit. Les clés absentes ici prennent les valeurs du paquet
 * (`packages/form/config/form.php`).
 */
return [
    // Le compte de l'app mobile vient de son jeton Sanctum.
    'guard' => 'sanctum',

    // Les routes de l'API sous `/api`, noms `api.` (`api.v1.forms.show`…).
    'routes' => [
        'enabled' => true,
        'prefix' => 'api',
        'name' => 'api.',
        'middleware' => ['api'],
    ],

    // L'envoi de fichiers réservé à un compte connecté (décision 2026-11-19-contrat-champ-upload-photos).
    'uploads' => [
        'guests' => false,
        'middleware' => ['throttle:60,1'],
    ],
];
