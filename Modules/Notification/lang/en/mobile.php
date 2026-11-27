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
        'requests' => 'Requests',
        // La page d'une notification et son bouton, à défaut du libellé servi.
        'detail' => 'Notification',
        // Le suivi d'une demande, sur la page d'une notification (décision 2026-11-20-suivi-demande).
        'history' => 'Request follow-up',
        // Le lecteur, dans le suivi d'une demande (décision 2026-11-20-suivi-conversation).
        'you' => 'You',
        // Près de la date d'une étape modifiée (décision 2026-11-21-etape-modifiee-date).
        'edited' => 'Edited',
        'actions' => 'Actions',
        // Ce qu'est devenue sa notification (décision 2026-11-20-coches-lecture).
        'delivery' => ['sent' => 'Sent', 'delivered' => 'Delivered', 'read' => 'Read'],
        'open' => 'Open',
        'markAllRead' => 'Mark all as read',
        'empty' => 'No notifications yet.',
        // Recherche et filtres de la liste (décision 2026-11-20-filtres-notifications).
        'search' => 'Search…',
        'noResults' => 'No notification matches this search.',
        'unread' => 'Unread',
        'unreadCount' => 'Unread notifications: :count',
        'justNow' => 'just now',
        'minutesAgo' => ':count min ago',
        'hoursAgo' => ':count h ago',
        'yesterday' => 'yesterday',
        // « hier à 10:30 » (décision 2026-11-22-date-carte-heure).
        'yesterdayAt' => 'yesterday at :time',
        'daysAgo' => ':count days ago',
    ],
];
