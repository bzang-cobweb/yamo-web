<?php

return [
    'title' => 'Application',
    'navigation_group' => 'Paramètres',
    'sections' => [
        'general' => 'Présentation',
        'payment' => 'Paiement',
        'users' => 'Utilisateurs',
    ],
    'description' => 'Description',
    'payment_required' => 'Appliquer le paiement',
    'minimum_age' => 'Âge minimum',
    'years' => 'ans',
    'help' => [
        'description' => "Présentation de l'application sur la page d'accueil, une par langue.",
        'payment_required' => "Coché, une offre n'est plus visible des autres après sa date « Payé jusqu'au » ; sans date, elle n'est pas visible. Son auteur la voit toujours.",
        'minimum_age' => "Renseigné, la date de naissance devient obligatoire à l'inscription et dans les informations personnelles, et doit donner au moins cet âge. Vide : pas de limite.",
    ],
];
