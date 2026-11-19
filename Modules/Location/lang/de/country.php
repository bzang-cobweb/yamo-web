<?php

return [
    'singular' => 'Land',
    'plural' => 'Länder',
    'title' => 'Länder',
    'code' => 'ISO-Code',
    'name' => 'Name',
    'label' => 'Land',
    'native_name' => 'Name in Landessprache',
    'dial_code' => 'Vorwahl',
    'currency' => 'Währung',
    'required' => 'Wählen Sie ein Land.',
    'active' => 'Aktiv',
    'regions_count' => 'Regionen',
    'sections' => [
        'general' => 'Details',
    ],
    'help' => [
        'currency' => 'ISO-4217-Code, zum Beispiel XAF oder CHF: die Währung der Angebote in diesem Land.',
        'code' => 'ISO-3166-1-Alpha-2-Code, zum Beispiel CH.',
        'native_name' => 'Name des Landes in seiner eigenen Sprache, zum Beispiel Deutschland.',
        'dial_code' => 'Mit dem „+“, zum Beispiel +41.',
    ],
    // Décision 2026-11-19-pays-obligatoire-offre.
    'delete_in_use' => 'Dieses Land hat noch Angebote: Es kann nicht gelöscht werden.',
];
