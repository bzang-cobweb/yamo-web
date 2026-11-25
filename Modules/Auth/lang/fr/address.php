<?php

// Adresses du compte (`UserAddress`) : destinataire, formulaire et liste de
// l'app (décision 2026-11-18-useraddress-dans-auth). Les libellés de l'adresse
// elle-même (rue, NPA, ville) restent dans `location::address`.

return [
    'recipient' => 'Destinataire',
    'indication' => "Indications sur l'adresse",
    'indication_placeholder' => 'Ex. : derrière le marché, portail bleu',
    'street_help' => 'Tapez au moins 3 caractères : les adresses connues sont proposées.',
    'detach' => 'Retirer',
    'form' => [
        'create_title' => 'Ajouter une adresse',
        'edit_title' => 'Modifier une adresse',
        'save' => 'Enregistrer',
        'saved' => 'Adresse enregistrée.',
    ],
    'list' => [
        'title' => 'Mes adresses',
        'empty' => 'Aucune adresse pour l\'instant.',
        'create' => 'Ajouter une adresse',
        'confirm' => 'Retirer cette adresse ?',
        'detached' => 'Adresse retirée.',
    ],
];
