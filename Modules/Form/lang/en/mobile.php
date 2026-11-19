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
        // Liste à choix multiples et intervalle (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
        'confirm' => 'Confirm',
        'rangeMin' => 'Min',
        'rangeMax' => 'Max',
        'rangeOrder' => 'The maximum must be at least the minimum.',
        'stepOf' => 'Step :current of :total',
        // Champ de fichiers (décision 2026-11-19-contrat-champ-upload-photos).
        'uploadAdd' => 'Add photos',
        'uploadMain' => 'Main',
        'uploadMoveLeft' => 'Move one place left',
        'uploadMoveRight' => 'Move one place right',
        'uploadRemove' => 'Remove',
        'uploadSending' => 'Sending…',
        'uploadWrongType' => 'Format not accepted.',
        'uploadTooLarge' => 'File too large: :max at most.',
        'uploadTooMany' => 'At most :max files.',
        'uploadFailed' => 'Sending failed. Try again.',
    ],
    'list' => [
        // Filtres d'une liste (décision 2026-11-19-contrat-filtres-vitrine).
        'filters' => 'Filters',
        'applyFilters' => 'Apply filters',
        'clearFilters' => 'Clear filters',
        // Actions d'un item avec un écran et un formulaire (décision 2026-11-19-nuit-voir-editer-retirer).
        'view' => 'View',
        'edit' => 'Edit',
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
