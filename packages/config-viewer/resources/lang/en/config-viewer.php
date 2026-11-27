<?php

return [
    'title' => 'Configuration',
    'columns' => [
        'key' => 'Key',
        'value' => 'Value',
        'type' => 'Type',
        'origin' => 'Origin',
    ],
    'filters' => [
        'group' => 'Group',
        'origin' => 'Origin',
    ],
    'origins' => [
        'application' => 'Application',
        'module' => 'Module',
        'package' => 'Package',
    ],
    'masked' => 'Sensitive value, hidden',
    'files' => 'Files: :files',
    'no_file' => 'No file: set by a package or at runtime.',
    'empty' => 'No configuration entry',
];
