<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'tabs' => [
        'home' => 'Accueil',
        // L'onglet de recherche des offres (décision 2026-11-23-onglet-recherche).
        'search' => 'Recherche',
        'profile' => 'Profil',
    ],
    'auth' => [
        'createAccount' => 'Créer un compte',
        'codeSent' => 'Code envoyé à :destination.',
        'resend' => 'Renvoyer le code',
        'resent' => 'Nouveau code envoyé.',
        'accountDeleted' => 'Votre compte a été supprimé.',
        'restoreTitle' => 'Récupérer mon compte',
        'restoreMessage' => 'Votre compte sera supprimé définitivement le :date. Voulez-vous le récupérer ?',
        'restoreConfirm' => 'Récupérer mon compte',
        'restoreCancel' => 'Annuler',
        'accountRestored' => 'Votre compte a été récupéré.',
        'previousStep' => 'Étape précédente',
        'signIn' => 'Se connecter',
        'signInOrUp' => "Se connecter ou s'inscrire",
        'registerIntro' => 'Pas encore de compte avec cet identifiant : créez-le en quelques mots.',
        'guest' => [
            'notifications' => 'Connectez-vous pour suivre vos demandes, vos réservations et vos messages.',
            'profile' => 'Connectez-vous pour gérer votre profil, vos favoris et vos logements.',
        ],
    ],
    'profile' => [
        'personalInfo' => 'Informations personnelles',
        'details' => 'Mes informations',
        'privacy' => 'Confidentialité',
        'identity' => "S'identifier",
        'password' => 'Changer le mot de passe',
        'preferences' => 'Préférences',
        'addresses' => 'Mes adresses',
        'notifications' => 'Notifications',
        'passwordSaved' => 'Mot de passe modifié.',
        'addEmail' => 'Ajouter une adresse e-mail',
        'changeEmail' => "Changer d'adresse e-mail",
        'addPhone' => 'Ajouter un numéro',
        'changePhone' => 'Changer de numéro',
        'emailSaved' => 'Adresse e-mail enregistrée.',
        'phoneSaved' => 'Numéro enregistré.',
        'deleteAccount' => 'Supprimer mon compte',
        'signOut' => 'Se déconnecter',
        'title' => 'Profil',
        'saved' => 'Modifications enregistrées.',
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
