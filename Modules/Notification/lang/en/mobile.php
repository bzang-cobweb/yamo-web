<?php

// Textes de l'app pour l'onglet « Notifications », servis par GET /api/v1/messages
// (App\Support\MobileMessages ; décision 2026-11-19-contrat-centre-notifications).

return [
    'tabs' => [
        'notifications' => 'Notifications',
    ],
    'notifications' => [
        'title' => 'Notifications',
        // La page d'une notification et son bouton, à défaut du libellé servi.
        'detail' => 'Notification',
        // Le suivi d'une demande, sur la page d'une notification (décision 2026-11-20-suivi-demande).
        'history' => 'Request follow-up',
        // Le lecteur, dans le suivi d'une demande (décision 2026-11-20-suivi-conversation).
        'you' => 'You',
        'open' => 'Open',
        'markAllRead' => 'Mark all as read',
        'empty' => 'No notifications yet.',
        // Recherche et filtres de la liste (décision 2026-11-20-filtres-notifications).
        'search' => 'Search…',
        'noResults' => 'No notification matches this search.',
        'unread' => 'Unread',
        'justNow' => 'just now',
        'minutesAgo' => ':count min ago',
        'hoursAgo' => ':count h ago',
        'yesterday' => 'yesterday',
        'daysAgo' => ':count days ago',
    ],
];
