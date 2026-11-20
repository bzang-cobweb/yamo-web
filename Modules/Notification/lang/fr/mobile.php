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
        'open' => 'Ouvrir',
        'markAllRead' => 'Tout marquer comme lu',
        'empty' => "Aucune notification pour l'instant.",
        // Recherche et filtres de la liste (décision 2026-11-20-filtres-notifications).
        'search' => 'Rechercher un membre ou un logement…',
        'noResults' => 'Aucune notification ne correspond à cette recherche.',
        'unread' => 'Non lue',
        'justNow' => "à l'instant",
        'minutesAgo' => 'il y a :count min',
        'hoursAgo' => 'il y a :count h',
        'yesterday' => 'hier',
        'daysAgo' => 'il y a :count jours',
    ],
];
