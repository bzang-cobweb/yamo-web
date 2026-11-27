<?php

return [
    'save' => 'Enregistrer',
    'login' => [
        'title' => 'Se connecter',
        'subtitle' => 'Votre e-mail ou votre téléphone : un code vous est envoyé. Pas encore de compte ? Il est créé à la suite.',
        'submit' => 'Se connecter',
    ],
    'register' => [
        'title' => 'Créer un compte',
        'submit' => 'Créer mon compte',
        'contact_help' => 'Un e-mail, un téléphone ou les deux : un code de vérification part vers chacun.',
        'steps' => [
            'identity' => 'Vous',
            'contact' => 'Vous joindre',
            'password' => 'Mot de passe',
        ],
    ],
    'code' => [
        'label' => 'Code reçu',
        'title' => 'Saisir le code',
        'submit' => 'Valider',
        'send' => 'Envoyer le code',
    ],
    'email' => [
        'add' => 'Ajouter une adresse e-mail',
        'change' => "Changer d'adresse e-mail",
        // Vérifier l'adresse actuelle (décision 2026-11-27-verifier-email-telephone).
        'verify_link' => 'Vérifier mon adresse',
        'verify_title' => 'Vérifier mon adresse e-mail',
        'verify_subtitle' => 'Un code va être envoyé à cette adresse.',
        'verified' => 'Adresse e-mail vérifiée.',
        'not_verified' => 'Cette adresse n\'est pas encore vérifiée.',
    ],
    'phone' => [
        'add' => 'Ajouter un numéro',
        'change' => 'Changer de numéro',
        'verify_link' => 'Renvoyer le code',
        'verify_title' => 'Vérifier mon numéro',
        'verify_subtitle' => 'Un code va être envoyé par SMS à ce numéro.',
        'verified' => 'Numéro vérifié.',
        'not_verified' => 'Ce numéro n\'est pas encore vérifié.',
    ],
    'password_forgot' => [
        'title' => 'Mot de passe oublié',
        'subtitle' => 'Saisissez l\'e-mail ou le téléphone de votre compte : un code vous sera envoyé.',
        'subtitle_email' => 'Saisissez l\'e-mail de votre compte : un code vous sera envoyé.',
        'subtitle_phone' => 'Saisissez le téléphone de votre compte : un code vous sera envoyé.',
    ],
    'password_reset' => [
        'title' => 'Nouveau mot de passe',
        'submit' => 'Changer le mot de passe',
    ],
    'personal' => [
        'title' => 'Informations personnelles',
    ],
    'password' => [
        'title' => 'Changer le mot de passe',
        'create_title' => 'Créer un mot de passe',
        'submit' => 'Changer le mot de passe',
        'help' => '8 caractères au moins.',
    ],
    'preferences' => [
        'title' => 'Préférences',
        'locale_help' => "Langue de l'application.",
    ],
    'delete_account' => [
        'title' => 'Supprimer mon compte',
        'warning' => "Votre compte sera désactivé et vos appareils déconnectés. Il sera supprimé définitivement, avec vos données et votre photo, dans :days jours ; d'ici là, reconnectez-vous pour le récupérer. Dites-nous pourquoi vous partez : un code vous sera ensuite envoyé pour confirmer.",
        'reason' => 'Pourquoi partez-vous ?',
        'reason_help' => 'Votre réponse est lue par notre équipe et nous aide à améliorer yamo.',
        'no_destination' => 'Ajoutez une adresse e-mail ou un numéro pour recevoir le code de confirmation.',
        'submit' => 'Supprimer mon compte',
        'last_super_admin' => 'Vous êtes le dernier super administrateur : nommez-en un autre avant de supprimer votre compte.',
        'purged' => ':count compte(s) purgé(s).',
    ],

    // Âge minimum (décision 2026-11-19-age-minimum).
    'birth_date' => [
        'too_young' => 'Vous devez avoir au moins :age ans.',
        'help' => 'Au moins :age ans.',
    ],
];
