<?php

return [
    'title' => 'Migrationen',
    'columns' => [
        'table' => 'Tabelle',
        'migration' => 'Migration',
        'operation' => 'Vorgang',
        'details' => 'Änderungen',
        'origin' => 'Herkunft',
        'status' => 'Status',
    ],
    'filters' => [
        'table' => 'Tabelle',
        'origin' => 'Herkunft',
        'module' => 'Modul',
        'operation' => 'Vorgang',
        'status' => 'Status',
    ],
    'origins' => [
        'application' => 'Anwendung',
        'module' => 'Modul',
        'package' => 'Paket',
    ],
    'operations' => [
        'create' => 'Erstellung',
        'alter' => 'Änderung',
        'rename' => 'Umbenennung',
        'drop' => 'Löschung',
        'data' => 'Daten',
    ],
    'statuses' => [
        'ran' => 'Ausgeführt',
        'pending' => 'Ausstehend',
        'unknown' => 'Unbekannt',
    ],
    'detail' => [
        'open' => 'Tabelle anzeigen',
        'close' => 'Schließen',
        'state' => 'Zustand',
        'present' => 'Vorhanden',
        'dropped' => 'Gelöscht',
        'migrations' => 'Migrationsdateien',
        'columns' => 'Aktuelle Felder',
        'column' => 'Feld',
        'definition' => 'Definition',
        'no_column' => 'Kein Feld',
    ],
    'no_table' => 'Keine Tabelle',
    'dropped' => 'Von einer späteren Migration gelöscht',
    'columns_summary' => '{0} Keine Spalte|{1} 1 Spalte: :columns|[2,*] :count Spalten: :columns',
    'failures' => 'Wiedergabe abgebrochen, Änderungen eventuell unvollständig: :migrations',
    'empty' => 'Keine Migration',
];
