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
        'type' => 'Type',
    ],
    'origins' => [
        'application' => 'Application',
        'module' => 'Module',
        'package' => 'Paquet',
    ],
    'all' => 'Tout',
    'types' => [
        'string' => 'Texte',
        'integer' => 'Entier',
        'float' => 'Décimal',
        'boolean' => 'Booléen',
        'null' => 'Nul',
        'array' => 'Tableau',
        'enum' => 'Énumération',
        'closure' => 'Fonction',
        'object' => 'Objet',
    ],
    'masked' => 'Valeur sensible, masquée',
    'files' => 'Fichiers : :files',
    'no_file' => 'Aucun fichier : défini par un paquet ou à l’exécution.',
    'empty' => 'Aucune entrée de configuration',
];
