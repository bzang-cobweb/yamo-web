<?php

// Module Notification (décision 2026-11-19-contrat-centre-notifications).

return [
    // Filtre « qui agit » de la liste (décision 2026-11-20-filtres-notifications).
    'filter_status' => 'Status',
    'filter_type' => 'Type',
    'filter_user' => 'Sender',
    'filter_unread' => 'Unread',
    'filter_any' => 'All',
    'filter_yes' => 'Yes',
    'filter_no' => 'No',
    'pruned' => ':count notification(s) older than :days days deleted.',
    // Le nombre de cartes de la liste (décision 2026-11-22-notifications-nombre).
    'count' => '{0} No notifications|{1} :count notification|[2,*] :count notifications',
    // Le menu ⋮ de la page d'une notification, servi avec elle (décision 2026-11-27-menu-notification-serveur).
    'menu' => [
        'info' => 'Info',
    ],
];
