<?php

return [
    'title' => 'Configuration',
    'columns' => [
        'key' => 'Clé',
        'value' => 'Valeur',
        'type' => 'Type',
        'origin' => 'Origine',
    ],
    'filters' => [
        'group' => 'Groupe',
        'origin' => 'Origine',
        'module' => 'Module',
    ],
    'origins' => [
        'application' => 'Application',
        'module' => 'Module',
        'package' => 'Paquet',
    ],
    'masked' => 'Valeur sensible, masquée',
    'files' => 'Fichiers : :files',
    'no_file' => 'Aucun fichier : défini par un paquet ou à l’exécution.',
    'empty' => 'Aucune entrée de configuration',
];
