<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'home' => [
        'greeting' => 'Bonjour :name',
        'signedInAs' => 'Connecté en tant que :fullName',
        'email' => 'E-mail : :email',
        'signOut' => 'Se déconnecter',
        'profile' => 'Mon profil',
    ],
    'profile' => [
        'title' => 'Profil',
        'saved' => 'Modifications enregistrées.',
        'back' => 'Retour',
    ],
    'avatar' => [
        'label' => 'Photo de profil',
        'change' => 'Modifier la photo',
        'choose' => 'Choisir une photo',
        'remove' => 'Retirer la photo',
        'cancel' => 'Annuler',
        'uploading' => 'Envoi de la photo…',
    ],
];
