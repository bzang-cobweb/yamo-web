<?php

// Textes de l'app pour l'onglet « Notifications », servis par GET /api/v1/messages
// (App\Support\MobileMessages ; décision 2026-11-19-contrat-centre-notifications).

return [
    'tabs' => [
        'notifications' => 'Benachrichtigungen',
    ],
    'notifications' => [
        'title' => 'Benachrichtigungen',
        // La page des notifications ouverte depuis une carte de « Mes logements » (décision 2026-11-21-demandes-depuis-logements).
        'requests' => 'Anfragen',
        // La page d'une notification et son bouton, à défaut du libellé servi.
        'detail' => 'Benachrichtigung',
        // Le suivi d'une demande, sur la page d'une notification (décision 2026-11-20-suivi-demande).
        'history' => 'Verlauf der Anfrage',
        // Le lecteur, dans le suivi d'une demande (décision 2026-11-20-suivi-conversation).
        'you' => 'Du',
        // Ce qu'est devenue sa notification (décision 2026-11-20-coches-lecture).
        'delivery' => ['sent' => 'Gesendet', 'delivered' => 'Zugestellt', 'read' => 'Gelesen'],
        'open' => 'Öffnen',
        'markAllRead' => 'Alle als gelesen markieren',
        'empty' => 'Noch keine Benachrichtigungen.',
        // Recherche et filtres de la liste (décision 2026-11-20-filtres-notifications).
        'search' => 'Suchen…',
        'noResults' => 'Keine Benachrichtigung entspricht dieser Suche.',
        'unread' => 'Ungelesen',
        'justNow' => 'gerade eben',
        'minutesAgo' => 'vor :count Min.',
        'hoursAgo' => 'vor :count Std.',
        'yesterday' => 'gestern',
        'daysAgo' => 'vor :count Tagen',
    ],
];
