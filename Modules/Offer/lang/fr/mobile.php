<?php

// Textes de l'app pour les offres, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'offer' => [
        'unavailable' => "Cette offre n'est plus disponible.",
        'publishedOn' => 'Publiée le :date',
        'photoPosition' => ':current / :total',
        'details' => 'Caractéristiques',
        'description' => 'Description',
        // Avis (décision 2026-11-19-contrat-avis-offres).
        'reviews' => 'Avis',
        'noReviews' => 'Aucun avis pour le moment.',
        'writeReview' => 'Donner mon avis',
        'editReview' => 'Modifier mon avis',
        'signInToReview' => 'Connectez-vous pour donner votre avis.',
    ],
];
