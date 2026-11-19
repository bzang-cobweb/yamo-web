<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'form' => [
        'loading' => 'Chargement du formulaire…',
        'submitting' => 'Envoi…',
        'selectPlaceholder' => 'Choisir…',
        'showPassword' => 'Afficher le mot de passe',
        'hidePassword' => 'Masquer le mot de passe',
        'clearDate' => 'Effacer',
        'search' => 'Rechercher…',
        'retry' => 'Réessayer',
        'next' => 'Suivant',
        'previous' => 'Précédent',
        // Champ note en étoiles, lu par l'accessibilité.
        'ratingValue' => ':value sur 5',
        // Unité d'un champ, dans sa ligne d'aide (« En m² »).
        'unit' => 'En :unit',
        'stepOf' => 'Étape :current sur :total',
    ],
    'list' => [
        // Filtres d'une liste (décision 2026-11-19-contrat-filtres-vitrine).
        'filters' => 'Filtres',
        'applyFilters' => 'Afficher les offres',
        'clearFilters' => 'Effacer les filtres',
        'noFilterResults' => 'Aucune offre ne correspond à ces filtres.',
        // Cœur de favori sur une photo (décision 2026-11-19-contrat-vedette-favoris).
        'addFavorite' => 'Ajouter aux favoris',
        'removeFavorite' => 'Retirer des favoris',
        // Note moyenne d'un item, lue par l'accessibilité (décision 2026-11-19-contrat-avis-offres).
        'ratingSummary' => 'Note moyenne :average sur 5, :count avis',
        'loading' => 'Chargement de la liste…',
        'cancel' => 'Annuler',
        'noResults' => 'Aucun résultat.',
    ],
];
