<?php

return [
    'title' => 'Migrations',
    'columns' => [
        'table' => 'Table',
        'migration' => 'Migration',
        'operation' => 'Operation',
        'details' => 'Changes',
        'origin' => 'Origin',
        'status' => 'Status',
    ],
    'filters' => [
        'table' => 'Table',
        'origin' => 'Origin',
        'module' => 'Module',
        'operation' => 'Operation',
        'status' => 'Status',
    ],
    'origins' => [
        'application' => 'Application',
        'module' => 'Module',
        'package' => 'Package',
    ],
    'operations' => [
        'create' => 'Create',
        'alter' => 'Alter',
        'rename' => 'Rename',
        'drop' => 'Drop',
        'data' => 'Data',
    ],
    'statuses' => [
        'ran' => 'Ran',
        'pending' => 'Pending',
        'unknown' => 'Unknown',
    ],
    'detail' => [
        'open' => 'View table',
        'close' => 'Close',
        'state' => 'State',
        'present' => 'Present',
        'dropped' => 'Dropped',
        'migrations' => 'Migration files',
        'columns' => 'Current columns',
        'column' => 'Column',
        'definition' => 'Definition',
        'no_column' => 'No column',
    ],
    'no_table' => 'No table',
    'dropped' => 'Dropped by a later migration',
    'columns_summary' => '{0} No column|{1} 1 column: :columns|[2,*] :count columns: :columns',
    'failures' => 'Replay stopped, changes may be incomplete: :migrations',
    'empty' => 'No migration',
];
