<?php

return [
    'save' => 'Speichern',
    'login' => [
        'title' => 'Anmelden',
        'subtitle' => 'Ihre E-Mail-Adresse oder Telefonnummer: Sie erhalten einen Code. Noch kein Konto? Es wird anschließend erstellt.',
        'submit' => 'Anmelden',
    ],
    'register' => [
        'title' => 'Ihr Konto erstellen',
        'submit' => 'Mein Konto erstellen',
        'contact_help' => 'Eine E-Mail-Adresse, eine Telefonnummer oder beides: An jede wird ein Bestätigungscode gesendet.',
        'steps' => [
            'identity' => 'Über Sie',
            'contact' => 'So erreichen wir Sie',
            'password' => 'Passwort',
        ],
    ],
    'code' => [
        'label' => 'Erhaltener Code',
        'title' => 'Code eingeben',
        'submit' => 'Bestätigen',
        'send' => 'Code senden',
    ],
    'email' => [
        'add' => 'E-Mail-Adresse hinzufügen',
        'change' => 'E-Mail-Adresse ändern',
        // Vérifier l'adresse actuelle (décision 2026-11-27-verifier-email-telephone).
        'verify_link' => 'Meine Adresse bestätigen',
        'verify_title' => 'Meine E-Mail-Adresse bestätigen',
        'verify_subtitle' => 'An diese Adresse wird ein Code gesendet.',
        'verified' => 'E-Mail-Adresse bestätigt.',
        'not_verified' => 'Diese Adresse ist noch nicht bestätigt.',
    ],
    'phone' => [
        'add' => 'Telefonnummer hinzufügen',
        'change' => 'Telefonnummer ändern',
        'verify_link' => 'Code erneut senden',
        'verify_title' => 'Meine Nummer bestätigen',
        'verify_subtitle' => 'An diese Nummer wird ein Code per SMS gesendet.',
        'verified' => 'Telefonnummer bestätigt.',
        'not_verified' => 'Diese Nummer ist noch nicht bestätigt.',
    ],
    'password_forgot' => [
        'title' => 'Passwort vergessen',
        'subtitle' => 'Geben Sie die E-Mail-Adresse oder Telefonnummer Ihres Kontos ein: Wir senden Ihnen einen Code.',
        'subtitle_email' => 'Geben Sie die E-Mail-Adresse Ihres Kontos ein: Wir senden Ihnen einen Code.',
        'subtitle_phone' => 'Geben Sie die Telefonnummer Ihres Kontos ein: Wir senden Ihnen einen Code.',
    ],
    'password_reset' => [
        'title' => 'Neues Passwort',
        'submit' => 'Passwort ändern',
    ],
    // L'étape du mot de passe, après le code (décision 2026-11-28-connexion-panneau).
    'session' => [
        'password' => [
            'title' => 'Ihr Passwort',
            'submit' => 'Anmelden',
        ],
        'new_password' => [
            'title' => 'Wählen Sie ein Passwort',
            'submit' => 'Weiter',
        ],
        'wrong_password' => 'Falsches Passwort.',
        'too_many' => 'Zu viele Versuche. Melden Sie sich erneut an.',
        'password_missing' => 'Wählen Sie zuerst ein Passwort.',
    ],
    'personal' => [
        'title' => 'Persönliche Angaben',
    ],
    'password' => [
        'title' => 'Passwort ändern',
        'create_title' => 'Passwort erstellen',
        'submit' => 'Passwort ändern',
        'help' => 'Mindestens 8 Zeichen.',
    ],
    'preferences' => [
        'title' => 'Einstellungen',
        'locale_help' => 'Sprache der App.',
    ],
    'delete_account' => [
        'title' => 'Mein Konto löschen',
        'warning' => 'Ihr Konto wird deaktiviert und Ihre Geräte werden abgemeldet. Es wird in :days Tagen endgültig gelöscht, mit Ihren Daten und Ihrem Foto; bis dahin melden Sie sich erneut an, um es wiederherzustellen. Sagen Sie uns, warum Sie gehen: Danach erhalten Sie einen Code zur Bestätigung.',
        'reason' => 'Warum gehen Sie?',
        'reason_help' => 'Ihre Antwort wird von unserem Team gelesen und hilft uns, yamo zu verbessern.',
        'no_destination' => 'Fügen Sie eine E-Mail-Adresse oder eine Telefonnummer hinzu, um den Bestätigungscode zu erhalten.',
        'submit' => 'Mein Konto löschen',
        'last_super_admin' => 'Sie sind der letzte Super-Administrator: Ernennen Sie einen anderen, bevor Sie Ihr Konto löschen.',
        'purged' => ':count Konto/Konten endgültig gelöscht.',
    ],

    // Âge minimum (décision 2026-11-19-age-minimum).
    'birth_date' => [
        'too_young' => 'Sie müssen mindestens :age Jahre alt sein.',
        'help' => 'Mindestens :age Jahre.',
    ],
];
