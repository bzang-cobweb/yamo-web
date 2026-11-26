<?php

// App texts for offers, served by GET /api/v1/messages
// (App\Support\MobileMessages). Variables use the :name format.

return [
    // L'accueil : « Voir tout » d'une ville ouvre la recherche filtrée sur elle (décision 2026-11-23-accueil-voir-tout).
    'home' => [
        'seeAll' => 'See all',
        // Le bloc en haut de l'accueil (décision 2026-11-25-bloc-a-la-une).
        'featured' => 'Featured',
    ],
    'offer' => [
        'unavailable' => 'This offer is no longer available.',
        'publishedOn' => 'Published on :date',
        'photoPosition' => ':current / :total',
        'details' => 'Features',
        'rules' => 'Rules',
        'description' => 'Description',
        // La description repliée à quelques lignes (décision 2026-11-22-description-voir-plus).
        'more' => 'Show more',
        // Indications sur l'adresse, après la description (décision 2026-11-19-fiche-offre-ordre).
        'location' => 'Location',
        // Avis (décision 2026-11-19-contrat-avis-offres).
        // Liste complète des avis (décision 2026-11-19-nuit-tous-les-avis).
        'allReviews' => 'See all :count reviews',
        'reviews' => 'Reviews',
        'noReviews' => 'No reviews yet.',
        'writeReview' => 'Write a review',
        'editReview' => 'Edit my review',
        'signInToReview' => 'Sign in to write a review.',
        // Le lien vers la fiche, sous les lignes d'une demande (décision 2026-11-20-lignes-demande).
        'view' => 'View the home',
    ],
];
