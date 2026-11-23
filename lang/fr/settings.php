<?php

return [
    'title' => 'Application',
    'navigation_group' => 'Paramètres',
    'sections' => [
        'home' => "Accueil de l'app",
        'general' => 'Présentation',
        'payment' => 'Paiement',
        'users' => 'Utilisateurs',
    ],
    'description' => 'Description',
    'payment_required' => 'Appliquer le paiement pour les propriétaires',
    'partner_payment_required' => 'Appliquer le paiement pour les partenaires',
    'minimum_age' => 'Âge minimum',
    'years' => 'ans',
    'home_offers_per_city' => 'Offres par ville',
    'home_partners' => 'Partenaires affichés',
    'help' => [
        'partner_payment_required' => "Coché, un partenaire n'est plus visible dans l'app après sa date « Expire le » ; sans date, il n'est pas visible. Décoché, la date est ignorée.",
        'home_count' => "Tirés au hasard à chaque affichage de l'accueil. Vide : 10.",
        'description' => "Présentation de l'application sur la page d'accueil, une par langue.",
        'payment_required' => "Coché, une offre n'est plus visible des autres après sa date « Payé jusqu'au » ; sans date, elle n'est pas visible. Son auteur la voit toujours.",
        'minimum_age' => "Renseigné, la date de naissance devient obligatoire à l'inscription et dans les informations personnelles, et doit donner au moins cet âge. Vide : pas de limite.",
    ],
];
