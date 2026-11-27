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
        'codeSent' => 'Code gesendet an :destination.',
        'resend' => 'Code erneut senden',
        'resent' => 'Neuer Code gesendet.',
        'accountDeleted' => 'Ihr Konto wurde gelöscht.',
        'restoreTitle' => 'Mein Konto wiederherstellen',
        'restoreMessage' => 'Ihr Konto wird am :date endgültig gelöscht. Möchten Sie es wiederherstellen?',
        'restoreConfirm' => 'Mein Konto wiederherstellen',
        'restoreCancel' => 'Abbrechen',
        'accountRestored' => 'Ihr Konto wurde wiederhergestellt.',
        'previousStep' => 'Vorheriger Schritt',
        'signIn' => 'Anmelden',
        'signInOrUp' => 'Anmelden oder registrieren',
        'registerIntro' => 'Noch kein Konto mit dieser Kennung: Erstellen Sie es mit wenigen Angaben.',
        'guest' => [
            'notifications' => 'Melden Sie sich an, um Ihre Anfragen, Buchungen und Nachrichten zu verfolgen.',
            'profile' => 'Melden Sie sich an, um Ihr Profil, Ihre Favoriten und Ihre Unterkünfte zu verwalten.',
        ],
    ],
    'profile' => [
        'personalInfo' => 'Persönliche Angaben',
        'details' => 'Meine Angaben',
        'privacy' => 'Datenschutz',
        'identity' => 'Identität bestätigen',
        'password' => 'Passwort ändern',
        'preferences' => 'Einstellungen',
        'addresses' => 'Meine Adressen',
        'notifications' => 'Benachrichtigungen',
        'residence' => 'Wohnadresse',
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
