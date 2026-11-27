<?php

return [
    'title' => 'Konfiguration',
    'columns' => [
        'key' => 'Schlüssel',
        'value' => 'Wert',
        'type' => 'Typ',
        'origin' => 'Herkunft',
    ],
    'filters' => [
        'group' => 'Gruppe',
        'origin' => 'Herkunft',
    ],
    'origins' => [
        'application' => 'Anwendung',
        'module' => 'Modul',
        'package' => 'Paket',
    ],
    'masked' => 'Sensibler Wert, ausgeblendet',
    'files' => 'Dateien: :files',
    'no_file' => 'Keine Datei: von einem Paket oder zur Laufzeit gesetzt.',
    'empty' => 'Kein Konfigurationseintrag',
];
