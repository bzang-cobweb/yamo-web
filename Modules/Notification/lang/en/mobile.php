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
        'open' => 'Open',
        'markAllRead' => 'Mark all as read',
        'empty' => 'No notifications yet.',
        // Recherche et filtres de la liste (décision 2026-11-20-filtres-notifications).
        'search' => 'Search a member or a home…',
        'noResults' => 'No notification matches this search.',
        'unread' => 'Unread',
        'justNow' => 'just now',
        'minutesAgo' => ':count min ago',
        'hoursAgo' => ':count h ago',
        'yesterday' => 'yesterday',
        'daysAgo' => ':count days ago',
    ],
];
