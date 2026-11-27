<?php

// Confidentialité du profil (décision 2026-11-27-confidentialite).
return [
    'visibility' => [
        'title' => 'Visibilité du profil',
        'subtitle' => 'Choisissez qui peut voir votre profil.',
        'label' => 'Qui peut voir mon profil',
        'help' => 'Mes contacts : les membres avec qui vous avez une demande en commun. Propriétaires : les membres qui proposent au moins une offre.',
        'options' => [
            'anyone' => 'Tout le monde sur yamo',
            'owners' => 'Les propriétaires',
            'connections' => 'Mes contacts',
            'owners_and_connections' => 'Les propriétaires et mes contacts',
        ],
    ],
    'reviews' => [
        'title' => 'Avis',
        'label' => 'Montrer mon pays, ma région et ma ville',
        'help' => 'Sous vos avis, à côté de votre prénom.',
    ],
    'data' => [
        'title' => 'Mes données',
        'subtitle' => 'Nous préparons un fichier de vos données (JSON) et l\'envoyons à votre adresse e-mail vérifiée.',
        'submit' => 'Demander mes données',
        'sent' => 'Vos données ont été envoyées par e-mail.',
        'no_verified_email' => 'Vérifiez d\'abord votre adresse e-mail : vos données ne sont envoyées qu\'à une adresse vérifiée.',
        'mail' => [
            'subject' => 'Vos données yamo',
            'intro' => 'Vous trouverez en pièce jointe les données de votre compte yamo, au format JSON.',
            'ignore' => 'Si vous n\'êtes pas à l\'origine de cette demande, changez d\'adresse e-mail depuis votre profil et contactez-nous.',
        ],
    ],
];
