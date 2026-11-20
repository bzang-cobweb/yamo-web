<?php

// Textes de l'app pour les offres, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'offer' => [
        'unavailable' => "Cette offre n'est plus disponible.",
        'publishedOn' => 'Publiée le :date',
        'photoPosition' => ':current / :total',
        'details' => 'Caractéristiques',
        'rules' => 'Règles',
        'description' => 'Description',
        // Indications sur l'adresse, après la description (décision 2026-11-19-fiche-offre-ordre).
        'location' => 'Localisation',
        // Avis (décision 2026-11-19-contrat-avis-offres).
        // Liste complète des avis (décision 2026-11-19-nuit-tous-les-avis).
        'allReviews' => 'Voir tous les avis',
        'reviews' => 'Avis',
        'noReviews' => 'Aucun avis pour le moment.',
        'writeReview' => 'Donner mon avis',
        'editReview' => 'Modifier mon avis',
        'signInToReview' => 'Connectez-vous pour donner votre avis.',
        // Le lien vers la fiche, sous les lignes d'une demande (décision 2026-11-20-lignes-demande).
        'view' => 'Voir le logement',
    ],
];
