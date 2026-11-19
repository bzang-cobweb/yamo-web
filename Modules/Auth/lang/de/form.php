<?php

return [
    'save' => 'Speichern',
    'login' => [
        'title' => 'Anmelden',
        'subtitle' => 'Zugang zu Ihrem yamo-Konto.',
        'submit' => 'Anmelden',
    ],
    'register' => [
        'title' => 'Konto erstellen',
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
    ],
    'phone' => [
        'add' => 'Telefonnummer hinzufügen',
        'change' => 'Telefonnummer ändern',
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
    'personal' => [
        'title' => 'Persönliche Angaben',
    ],
    'password' => [
        'title' => 'Passwort ändern',
        'submit' => 'Passwort ändern',
        'help' => 'Mindestens 8 Zeichen.',
    ],
    'preferences' => [
        'title' => 'Einstellungen',
        'locale_help' => 'Sprache der App.',
    ],
    'delete_account' => [
        'title' => 'Mein Konto löschen',
        'warning' => 'Ihr Konto wird deaktiviert und Ihre Geräte werden abgemeldet. Es wird in :days Tagen endgültig gelöscht, mit Ihren Daten und Ihrem Foto; bis dahin können Sie uns kontaktieren, um es wiederherzustellen. Bestätigen Sie mit Ihrem Passwort.',
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
