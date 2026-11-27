<?php

return [
    /*
     * The date formats of a date field, served with it and applied as is by the app (`Intl.DateTimeFormat`
     * options): changing them here is enough, with no new app version (decision
     * 2026-11-25-dates-formatees-serveur).
     * - `value`: the chosen date, in the field (« 12 avril 1990 »);
     * - `month`: the calendar header (« novembre 2026 »);
     * - `weekday`: the days of the week, at the top of the columns (« lun. »).
     */
    'date_formats' => [
        'value' => ['day' => 'numeric', 'month' => 'long', 'year' => 'numeric'],
        'month' => ['month' => 'long', 'year' => 'numeric'],
        'weekday' => ['weekday' => 'short'],
    ],
];
