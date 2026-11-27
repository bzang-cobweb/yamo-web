<?php

// Module Notification (décision 2026-11-19-contrat-centre-notifications).

return [
    // Filtre « qui agit » de la liste (décision 2026-11-20-filtres-notifications).
    'filter_status' => 'Statut',
    'filter_type' => 'Type',
    'filter_user' => 'Expéditeur',
    'filter_unread' => 'Non lues',
    'filter_any' => 'Tous',
    'filter_yes' => 'Oui',
    'filter_no' => 'Non',
    'pruned' => ':count notification(s) de plus de :days jours effacée(s).',
    // Le nombre de cartes de la liste (décision 2026-11-22-notifications-nombre).
    'count' => '{0} Aucune notification|{1} :count notification|[2,*] :count notifications',
    // Le menu ⋮ de la page d'une notification, servi avec elle (décision 2026-11-27-menu-notification-serveur).
    'menu' => [
        'info' => 'Info',
    ],
];
