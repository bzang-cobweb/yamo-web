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
        'module' => 'Module',
        'type' => 'Type',
    ],
    'origins' => [
        'application' => 'Application',
        'module' => 'Module',
        'package' => 'Package',
    ],
    'all' => 'All',
    'types' => [
        'string' => 'String',
        'integer' => 'Integer',
        'float' => 'Float',
        'boolean' => 'Boolean',
        'null' => 'Null',
        'array' => 'Array',
        'enum' => 'Enum',
        'closure' => 'Closure',
        'object' => 'Object',
    ],
    'masked' => 'Sensitive value, hidden',
    'files' => 'Files: :files',
    'no_file' => 'No file: set by a package or at runtime.',
    'empty' => 'No configuration entry',
];
