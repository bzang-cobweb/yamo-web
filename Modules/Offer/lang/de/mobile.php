<?php

// App texts for offers, served by GET /api/v1/messages
// (App\Support\MobileMessages). Variables use the :name format.

return [
    'offer' => [
        'unavailable' => 'Dieses Angebot ist nicht mehr verfügbar.',
        'publishedOn' => 'Veröffentlicht am :date',
        'photoPosition' => ':current / :total',
        'details' => 'Merkmale',
        'rules' => 'Regeln',
        'description' => 'Beschreibung',
        // Indications sur l'adresse, après la description (décision 2026-11-19-fiche-offre-ordre).
        'location' => 'Lage',
        // Avis (décision 2026-11-19-contrat-avis-offres).
        // Liste complète des avis (décision 2026-11-19-nuit-tous-les-avis).
        'allReviews' => 'Alle Bewertungen ansehen',
        'reviews' => 'Bewertungen',
        'noReviews' => 'Noch keine Bewertungen.',
        'writeReview' => 'Bewertung schreiben',
        'editReview' => 'Meine Bewertung bearbeiten',
        'signInToReview' => 'Melden Sie sich an, um eine Bewertung zu schreiben.',
    ],
];
