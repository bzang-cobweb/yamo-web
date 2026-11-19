<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'nav' => [
        'back' => 'Zurück',
        'close' => 'Schließen',
    ],
    'error' => [
        'unknown' => 'Ein Fehler ist aufgetreten. Bitte erneut versuchen.',
    ],
    // Écran d'entrée : pays et langue (décision 2026-11-18-ecran-entree-toujours).
    'entry' => [
        'title' => 'Willkommen',
        'subtitle' => 'Wählen Sie Ihr Land und Ihre Sprache.',
        // Le pays où l'on utilise l'app (son espace), pas la nationalité.
        'country' => 'Welches Land?',
        'language' => 'Sprache',
        'chooseCountry' => 'Land wählen',
        'enter' => 'Weiter',
        'changeCountry' => 'Land wechseln',
    ],
];
