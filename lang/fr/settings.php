<?php

return [
    'title' => 'Application',
    'navigation_group' => 'Paramètres',
    'sections' => [
        'general' => 'Présentation',
        'payment' => 'Paiement',
    ],
    'description' => 'Description',
    'payment_required' => 'Appliquer le paiement',
    'help' => [
        'description' => "Présentation de l'application sur la page d'accueil, une par langue.",
        'payment_required' => "Coché, une offre n'est plus visible des autres après sa date d'expiration ; sans date, elle n'est pas visible. Son auteur la voit toujours.",
    ],
];
