<?php

return [
    'name' => 'Form',

    /*
     * Les formats des dates d'un champ date, servis avec lui et appliqués tels quels par l'app (options de
     * `Intl.DateTimeFormat`) : les changer ici suffit, sans nouvelle version de l'app (décision
     * 2026-11-25-dates-formatees-serveur).
     * - `value` : la date choisie, dans le champ (« 12 avril 1990 ») ;
     * - `month` : l'en-tête du calendrier (« novembre 2026 ») ;
     * - `weekday` : les jours de la semaine, en tête des colonnes (« lun. »).
     */
    'date_formats' => [
        'value' => ['day' => 'numeric', 'month' => 'long', 'year' => 'numeric'],
        'month' => ['month' => 'long', 'year' => 'numeric'],
        'weekday' => ['weekday' => 'short'],
    ],
];
