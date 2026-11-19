<?php

// Textes de l'app pour l'onglet « Notifications », servis par GET /api/v1/messages
// (App\Support\MobileMessages ; décision 2026-11-19-contrat-centre-notifications).

return [
    'tabs' => [
        'notifications' => 'Benachrichtigungen',
    ],
    'notifications' => [
        'title' => 'Benachrichtigungen',
        'markAllRead' => 'Alle als gelesen markieren',
        'empty' => 'Noch keine Benachrichtigungen.',
        'unread' => 'Ungelesen',
        'justNow' => 'gerade eben',
        'minutesAgo' => 'vor :count Min.',
        'hoursAgo' => 'vor :count Std.',
        'yesterday' => 'gestern',
        'daysAgo' => 'vor :count Tagen',
    ],
];
