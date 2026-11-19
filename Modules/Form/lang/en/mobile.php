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
        // Unité d'un champ, dans sa ligne d'aide (« En m² »).
        'unit' => 'In :unit',
        'stepOf' => 'Step :current of :total',
    ],
    'list' => [
        // Filtres d'une liste (décision 2026-11-19-contrat-filtres-vitrine).
        'filters' => 'Filters',
        'applyFilters' => 'Show offers',
        'clearFilters' => 'Clear filters',
        'noFilterResults' => 'No offer matches these filters.',
        // Cœur de favori sur une photo (décision 2026-11-19-contrat-vedette-favoris).
        'addFavorite' => 'Add to favorites',
        'removeFavorite' => 'Remove from favorites',
        // Note moyenne d'un item, lue par l'accessibilité (décision 2026-11-19-contrat-avis-offres).
        'ratingSummary' => 'Average rating :average out of 5, :count reviews',
        'loading' => 'Loading list…',
        'cancel' => 'Cancel',
        'noResults' => 'No results.',
    ],
];
