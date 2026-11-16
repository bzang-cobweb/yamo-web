<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'tabs' => [
        'home' => 'Accueil',
        'profile' => 'Profil',
    ],
    'home' => [
        'greeting' => 'Bonjour :name',
    ],
    'profile' => [
        'personalInfo' => 'Informations personnelles',
        'password' => 'Changer le mot de passe',
        'preferences' => 'Préférences',
        'passwordSaved' => 'Mot de passe modifié.',
        'signOut' => 'Se déconnecter',
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
