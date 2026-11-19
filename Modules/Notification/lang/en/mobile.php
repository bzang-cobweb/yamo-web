<?php

// Textes de l'app pour l'onglet « Notifications », servis par GET /api/v1/messages
// (App\Support\MobileMessages ; décision 2026-11-19-contrat-centre-notifications).

return [
    'tabs' => [
        'notifications' => 'Notifications',
    ],
    'notifications' => [
        'title' => 'Notifications',
        'markAllRead' => 'Mark all as read',
        'empty' => 'No notifications yet.',
        'unread' => 'Unread',
        'justNow' => 'just now',
        'minutesAgo' => ':count min ago',
        'hoursAgo' => ':count h ago',
        'yesterday' => 'yesterday',
        'daysAgo' => ':count days ago',
    ],
];
