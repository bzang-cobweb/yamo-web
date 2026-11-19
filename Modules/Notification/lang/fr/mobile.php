<?php

// Textes de l'app pour l'onglet « Notifications », servis par GET /api/v1/messages
// (App\Support\MobileMessages ; décision 2026-11-19-contrat-centre-notifications).

return [
    'tabs' => [
        'notifications' => 'Notifications',
    ],
    'notifications' => [
        'title' => 'Notifications',
        'markAllRead' => 'Tout marquer comme lu',
        'empty' => "Aucune notification pour l'instant.",
        'unread' => 'Non lue',
        'justNow' => "à l'instant",
        'minutesAgo' => 'il y a :count min',
        'hoursAgo' => 'il y a :count h',
        'yesterday' => 'hier',
        'daysAgo' => 'il y a :count jours',
    ],
];
