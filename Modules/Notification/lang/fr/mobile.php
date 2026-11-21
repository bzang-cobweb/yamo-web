<?php

// Textes de l'app pour l'onglet « Notifications », servis par GET /api/v1/messages
// (App\Support\MobileMessages ; décision 2026-11-19-contrat-centre-notifications).

return [
    'tabs' => [
        'notifications' => 'Notifications',
    ],
    'notifications' => [
        'title' => 'Notifications',
        // La page des notifications ouverte depuis une carte de « Mes logements » (décision 2026-11-21-demandes-depuis-logements).
        'requests' => 'Demandes',
        // La page d'une notification et son bouton, à défaut du libellé servi.
        'detail' => 'Notification',
        // Le suivi d'une demande, sur la page d'une notification (décision 2026-11-20-suivi-demande).
        'history' => 'Suivi de la demande',
        // Le lecteur, dans le suivi d'une demande (décision 2026-11-20-suivi-conversation).
        'you' => 'Toi',
        // Près de la date d'une étape modifiée (décision 2026-11-21-etape-modifiee-date).
        'edited' => 'Modifié',
        // Ce qu'est devenue sa notification (décision 2026-11-20-coches-lecture).
        'delivery' => ['sent' => 'Envoyée', 'delivered' => 'Reçue', 'read' => 'Lue'],
        'open' => 'Ouvrir',
        'markAllRead' => 'Tout marquer comme lu',
        'empty' => "Aucune notification pour l'instant.",
        // Recherche et filtres de la liste (décision 2026-11-20-filtres-notifications).
        'search' => 'Rechercher…',
        'noResults' => 'Aucune notification ne correspond à cette recherche.',
        'unread' => 'Non lue',
        'justNow' => "à l'instant",
        'minutesAgo' => 'il y a :count min',
        'hoursAgo' => 'il y a :count h',
        'yesterday' => 'hier',
        'daysAgo' => 'il y a :count jours',
    ],
];
