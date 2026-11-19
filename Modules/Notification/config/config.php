<?php

return [
    'name' => 'Notification',

    /*
     * Durée de conservation des notifications de l'app, en jours (décision
     * 2026-11-19-contrat-centre-notifications) : au-delà, la tâche quotidienne
     * `notification:prune` les efface.
     */
    'retention_days' => (int) env('NOTIFICATION_RETENTION_DAYS', 90),

    /** Notifications par page de la liste de l'app. */
    'per_page' => 20,
];
