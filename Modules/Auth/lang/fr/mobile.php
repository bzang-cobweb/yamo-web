<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'tabs' => [
        'home' => 'Accueil',
        'profile' => 'Profil',
    ],
    'auth' => [
        'createAccount' => 'Créer un compte',
        'haveAccount' => "J'ai déjà un compte",
        'codeSent' => 'Code envoyé à :destination.',
        'resend' => 'Renvoyer le code',
        'resent' => 'Nouveau code envoyé.',
        'forgotPassword' => 'Mot de passe oublié ?',
        'forgotPasswordTitle' => 'Mot de passe oublié',
        'passwordReset' => 'Mot de passe modifié, connectez-vous.',
        'accountDeleted' => 'Votre compte a été supprimé.',
        'restoreTitle' => 'Récupérer mon compte',
        'restoreMessage' => 'Votre compte sera supprimé définitivement le :date. Voulez-vous le récupérer ?',
        'restoreConfirm' => 'Récupérer mon compte',
        'restoreCancel' => 'Annuler',
        'accountRestored' => 'Votre compte a été récupéré.',
        'previousStep' => 'Étape précédente',
        'signIn' => 'Se connecter',
    ],
    'profile' => [
        'personalInfo' => 'Informations personnelles',
        'password' => 'Changer le mot de passe',
        'preferences' => 'Préférences',
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
