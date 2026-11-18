<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'form' => [
        'loading' => 'Loading form…',
        'submitting' => 'Sending…',
        'selectPlaceholder' => 'Choose…',
        'showPassword' => 'Show password',
        'hidePassword' => 'Hide password',
        'clearDate' => 'Clear',
        'search' => 'Search…',
        'retry' => 'Try again',
        'next' => 'Next',
        'previous' => 'Previous',
        // Champ note en étoiles, lu par l'accessibilité.
        'ratingValue' => ':value out of 5',
        'stepOf' => 'Step :current of :total',
    ],
    'list' => [
        // Note moyenne d'un item, lue par l'accessibilité (décision 2026-11-19-contrat-avis-offres).
        'ratingSummary' => 'Average rating :average out of 5, :count reviews',
        'loading' => 'Loading list…',
        'cancel' => 'Cancel',
        'noResults' => 'No results.',
    ],
];
