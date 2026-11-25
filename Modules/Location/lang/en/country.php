<?php

return [
    'singular' => 'country',
    'plural' => 'countries',
    'title' => 'Countries',
    'code' => 'ISO code',
    'name' => 'Name',
    'label' => 'Country',
    'native_name' => 'Native name',
    'region_label' => 'Name of the regions',
    'dial_code' => 'Dialling code',
    'currency' => 'Currency',
    'timezone' => 'Time zone',
    'required' => 'Choose a country.',
    'active' => 'Active',
    'regions_count' => 'Regions',
    'sections' => [
        'general' => 'Details',
    ],
    'help' => [
        'currency' => 'ISO 4217 code, for example XAF or CHF: the currency of offers in this country.',
        'timezone' => 'For example Africa/Douala: “today” for the offers of this country (booking dates).',
        'code' => 'ISO 3166-1 alpha-2 code, for example CH.',
        'native_name' => 'Name of the country in its own language, for example Deutschland.',
        'region_label' => 'Canton, District, State…: the label of the region field and filter. Empty: “Region”.',
        'dial_code' => 'With the "+", for example +41.',
    ],
    // Décision 2026-11-19-pays-obligatoire-offre.
    'delete_in_use' => 'This country still has offers: it cannot be deleted.',
];
