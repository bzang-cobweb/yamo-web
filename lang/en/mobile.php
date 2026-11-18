<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'nav' => [
        'back' => 'Back',
    ],
    'error' => [
        'unknown' => 'Something went wrong. Please try again.',
    ],
    // Écran d'entrée : pays et langue (décision 2026-11-18-ecran-entree-toujours).
    'entry' => [
        'title' => 'Welcome',
        'subtitle' => 'Choose your country and language.',
        'country' => 'Country',
        'language' => 'Language',
        'chooseCountry' => 'Choose a country',
        'enter' => 'Enter',
        'changeCountry' => 'Change country',
    ],
];
