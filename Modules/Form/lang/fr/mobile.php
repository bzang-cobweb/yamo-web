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
        // Liste à choix multiples et intervalle (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
        'confirm' => 'Valider',
        'rangeMin' => 'Min',
        'rangeMax' => 'Max',
        'rangeOrder' => 'Le maximum doit être au moins égal au minimum.',
        'stepOf' => 'Étape :current sur :total',
        // Champ de fichiers (décision 2026-11-19-contrat-champ-upload-photos).
        'uploadAdd' => 'Ajouter des photos',
        'uploadMain' => 'Principale',
        'uploadMoveLeft' => "Avancer d'une place",
        'uploadMoveRight' => "Reculer d'une place",
        'uploadRemove' => 'Retirer',
        // Liste de groupes (décision 2026-11-19-contrat-offer-informations).
        'repeaterRemove' => 'Retirer ce bloc',
        'repeaterAdd' => 'Ajouter',
        'repeaterUntitled' => 'Sans titre',
        // Champ qui recharge le formulaire (décision 2026-11-19-contrat-langue-saisie-allemand).
        'reloadConfirm' => 'Changer ? Les modifications non enregistrées seront perdues.',
        'reloadContinue' => 'Continuer',
        'uploadSending' => 'Envoi…',
        'uploadWrongType' => 'Format non accepté.',
        'uploadTooLarge' => 'Fichier trop lourd : :max au plus.',
        'uploadTooMany' => 'Au plus :max fichiers.',
        'uploadFailed' => "L'envoi a échoué. Réessayez.",
    ],
    'list' => [
        // Filtres d'une liste (décision 2026-11-19-contrat-filtres-vitrine).
        'filters' => 'Filtres',
        'applyFilters' => 'Appliquer les filtres',
        'clearFilters' => 'Effacer les filtres',
        // Actions d'un item avec un écran et un formulaire (décision 2026-11-19-nuit-voir-editer-retirer).
        'view' => 'Voir',
        'edit' => 'Éditer',
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
