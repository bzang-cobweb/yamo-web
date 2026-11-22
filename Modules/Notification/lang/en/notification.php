<?php

// Module Notification (décision 2026-11-19-contrat-centre-notifications).

return [
    // Filtre « qui agit » de la liste (décision 2026-11-20-filtres-notifications).
    'filter_type' => 'Type',
    'filter_user' => 'Member',
    'filter_unread' => 'Unread',
    'filter_any' => 'All',
    'filter_yes' => 'Yes',
    'filter_no' => 'No',
    'pruned' => ':count notification(s) older than :days days deleted.',
    // Le nombre de cartes de la liste (décision 2026-11-22-notifications-nombre).
    'count' => '{0} No notifications|{1} :count notification|[2,*] :count notifications',
];
