<?php

return [
    /*
     * The guard that reads the requesting account (forms and lists built for it, upload owner, upload checks);
     * `null`: Laravel's default guard. Nothing is imposed by the package (decision 2026-11-27-paquet-form-acces).
     */
    'guard' => null,

    /*
     * The package's API routes: on or off, URL prefix, route name prefix and middleware group.
     */
    'routes' => [
        'enabled' => true,
        'prefix' => 'api',
        'name' => 'api.',
        'middleware' => ['api'],
    ],

    /*
     * Files of an `upload` field, sent before saving the form:
     * - `guests`: `true` lets a visitor without an account upload (the file then belongs to whoever holds its random
     *   identifier); `false` requires an account of `guard`;
     * - `middleware`: added to the upload route (throttling…).
     */
    'uploads' => [
        'guests' => false,
        'middleware' => ['throttle:60,1'],
    ],

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
