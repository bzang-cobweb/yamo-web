<?php

// « Mes numéros » : le numéro principal et les numéros supplémentaires (décision 2026-11-27-plusieurs-numeros).
return [
    'title' => 'Numéros supplémentaires',
    'list' => [
        'title' => 'Mes numéros',
        'create' => 'Ajouter un numéro',
        'primary' => 'Principal',
        'no_primary' => 'Aucun numéro principal',
        'add_primary' => 'Ajouter',
        'change' => 'Changer',
        'verify' => 'Vérifier',
        'not_verified' => 'non vérifié',
        'make_primary' => 'Rendre principal',
        'make_primary_confirm' => 'Utiliser ce numéro pour vous connecter et recevoir vos codes ? Le numéro principal actuel restera dans vos numéros.',
        'made_primary' => 'Numéro principal changé.',
        'remove' => 'Retirer',
        'remove_confirm' => 'Retirer ce numéro ?',
        'removed' => 'Numéro retiré.',
    ],
    'form' => [
        'title' => 'Ajouter un numéro',
        'phone' => 'Numéro',
        'help' => 'Un code vous sera envoyé par SMS à ce numéro.',
        'saved' => 'Numéro ajouté.',
    ],
    'errors' => [
        'primary' => "C'est déjà votre numéro principal.",
        'duplicate' => 'Ce numéro est déjà dans vos numéros.',
    ],
    'verified_at' => 'Vérifié le',
    'added_at' => 'Ajouté le',
];
