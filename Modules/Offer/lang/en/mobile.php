<?php

// App texts for offers, served by GET /api/v1/messages
// (App\Support\MobileMessages). Variables use the :name format.

return [
    'offer' => [
        'unavailable' => 'This offer is no longer available.',
        'publishedOn' => 'Published on :date',
        'photoPosition' => ':current / :total',
        'details' => 'Features',
        'rules' => 'House rules',
        'description' => 'Description',
        // Indications sur l'adresse, après la description (décision 2026-11-19-fiche-offre-ordre).
        'location' => 'Location',
        // Avis (décision 2026-11-19-contrat-avis-offres).
        // Liste complète des avis (décision 2026-11-19-nuit-tous-les-avis).
        'allReviews' => 'See all reviews',
        'reviews' => 'Reviews',
        'noReviews' => 'No reviews yet.',
        'writeReview' => 'Write a review',
        'editReview' => 'Edit my review',
        'signInToReview' => 'Sign in to write a review.',
    ],
];
