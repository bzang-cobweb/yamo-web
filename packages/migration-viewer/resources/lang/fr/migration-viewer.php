<?php

return [
    'title' => 'Migrations',
    'columns' => [
        'table' => 'Table',
        'migration' => 'Migration',
        'operation' => 'Opération',
        'details' => 'Changements',
        'origin' => 'Origine',
        'status' => 'État',
    ],
    'filters' => [
        'table' => 'Table',
        'origin' => 'Origine',
        'module' => 'Module',
        'operation' => 'Opération',
        'status' => 'État',
    ],
    'origins' => [
        'application' => 'Application',
        'module' => 'Module',
        'package' => 'Paquet',
    ],
    'operations' => [
        'create' => 'Création',
        'alter' => 'Modification',
        'rename' => 'Renommage',
        'drop' => 'Suppression',
        'data' => 'Données',
    ],
    'statuses' => [
        'ran' => 'Exécutée',
        'pending' => 'En attente',
        'unknown' => 'Inconnu',
    ],
    'detail' => [
        'open' => 'Voir la table',
        'close' => 'Fermer',
        'state' => 'État',
        'present' => 'Présente',
        'dropped' => 'Supprimée',
        'migrations' => 'Fichiers de migration',
        'columns' => 'Champs actuels',
        'column' => 'Champ',
        'definition' => 'Définition',
        'no_column' => 'Aucun champ',
    ],
    'no_table' => 'Aucune table',
    'dropped' => 'Supprimée par une migration ultérieure',
    'columns_summary' => '{0} Aucune colonne|{1} 1 colonne : :columns|[2,*] :count colonnes : :columns',
    'failures' => 'Relecture interrompue, changements peut-être incomplets : :migrations',
    'empty' => 'Aucune migration',
];
