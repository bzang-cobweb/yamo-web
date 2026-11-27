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
        'module' => 'Modul',
        'type' => 'Typ',
    ],
    'origins' => [
        'application' => 'Anwendung',
        'module' => 'Modul',
        'package' => 'Paket',
    ],
    'all' => 'Alle',
    'types' => [
        'string' => 'Text',
        'integer' => 'Ganzzahl',
        'float' => 'Dezimalzahl',
        'boolean' => 'Boolescher Wert',
        'null' => 'Null',
        'array' => 'Array',
        'enum' => 'Aufzählung',
        'closure' => 'Funktion',
        'object' => 'Objekt',
    ],
    'masked' => 'Sensibler Wert, ausgeblendet',
    'files' => 'Dateien: :files',
    'no_file' => 'Keine Datei: von einem Paket oder zur Laufzeit gesetzt.',
    'empty' => 'Kein Konfigurationseintrag',
];
