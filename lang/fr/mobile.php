<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'nav' => [
        'back' => 'Retour',
    ],
    'error' => [
        'unknown' => 'Une erreur est survenue. Réessayez.',
    ],
    // Écran d'entrée : pays et langue (décision 2026-11-18-ecran-entree-toujours).
    'entry' => [
        'title' => 'Bienvenue',
        'subtitle' => 'Choisissez votre pays et votre langue.',
        // Le pays où l'on utilise l'app (son espace), pas la nationalité.
        'country' => "Dans quel pays utilisez-vous l'app ?",
        'language' => 'Langue',
        'chooseCountry' => 'Choisir un pays',
        'enter' => 'Entrer',
        'changeCountry' => 'Changer de pays',
    ],
];
