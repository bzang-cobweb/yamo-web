<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'tabs' => [
        'home' => 'Startseite',
        // L'onglet de recherche des offres (décision 2026-11-23-onglet-recherche).
        'search' => 'Suche',
        'profile' => 'Profil',
    ],
    'auth' => [
        'createAccount' => 'Konto erstellen',
        'haveAccount' => 'Ich habe bereits ein Konto',
        'codeSent' => 'Code gesendet an :destination.',
        'resend' => 'Code erneut senden',
        'resent' => 'Neuer Code gesendet.',
        'forgotPassword' => 'Passwort vergessen?',
        'forgotPasswordTitle' => 'Passwort vergessen',
        'passwordReset' => 'Passwort geändert, bitte melden Sie sich an.',
        'accountDeleted' => 'Ihr Konto wurde gelöscht.',
        'restoreTitle' => 'Mein Konto wiederherstellen',
        'restoreMessage' => 'Ihr Konto wird am :date endgültig gelöscht. Möchten Sie es wiederherstellen?',
        'restoreConfirm' => 'Mein Konto wiederherstellen',
        'restoreCancel' => 'Abbrechen',
        'accountRestored' => 'Ihr Konto wurde wiederhergestellt.',
        'previousStep' => 'Vorheriger Schritt',
        'signIn' => 'Anmelden',
    ],
    'profile' => [
        'personalInfo' => 'Persönliche Angaben',
        'identity' => 'Identität bestätigen',
        'password' => 'Passwort ändern',
        'preferences' => 'Einstellungen',
        'addresses' => 'Meine Adressen',
        'passwordSaved' => 'Passwort geändert.',
        'addEmail' => 'E-Mail-Adresse hinzufügen',
        'changeEmail' => 'E-Mail-Adresse ändern',
        'addPhone' => 'Telefonnummer hinzufügen',
        'changePhone' => 'Telefonnummer ändern',
        'emailSaved' => 'E-Mail-Adresse gespeichert.',
        'phoneSaved' => 'Telefonnummer gespeichert.',
        'deleteAccount' => 'Mein Konto löschen',
        'signOut' => 'Abmelden',
        'title' => 'Profil',
        'saved' => 'Änderungen gespeichert.',
    ],
    'avatar' => [
        'label' => 'Profilfoto',
        'change' => 'Foto ändern',
        'choose' => 'Foto auswählen',
        'remove' => 'Foto entfernen',
        'cancel' => 'Abbrechen',
        'uploading' => 'Foto wird hochgeladen…',
    ],
];
