<?php

// App texts for offers, served by GET /api/v1/messages
// (App\Support\MobileMessages). Variables use the :name format.

return [
    // L'accueil : « Voir tout » d'une ville ouvre la recherche filtrée sur elle (décision 2026-11-23-accueil-voir-tout).
    'home' => [
        'seeAll' => 'Alle anzeigen',
        // Le bloc en haut de l'accueil (décision 2026-11-25-bloc-a-la-une).
        'featured' => 'Im Fokus',
    ],
    'offer' => [
        'unavailable' => 'Dieses Angebot ist nicht mehr verfügbar.',
        'publishedOn' => 'Veröffentlicht am :date',
        'photoPosition' => ':current / :total',
        'details' => 'Merkmale',
        'rules' => 'Regeln',
        'description' => 'Beschreibung',
        // La description repliée à quelques lignes (décision 2026-11-22-description-voir-plus).
        'more' => 'Mehr anzeigen',
        'less' => 'Weniger anzeigen',
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
        // Le lien vers la fiche, sous les lignes d'une demande (décision 2026-11-20-lignes-demande).
        'view' => 'Unterkunft ansehen',
    ],
];
