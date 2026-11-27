<?php

// Confidentialité du profil (décision 2026-11-27-confidentialite).
return [
    'visibility' => [
        'title' => 'Sichtbarkeit des Profils',
        'subtitle' => 'Wählen Sie, wer Ihr Profil sehen kann.',
        'label' => 'Wer mein Profil sehen kann',
        'help' => 'Meine Kontakte: Mitglieder, mit denen Sie eine Anfrage teilen. Eigentümer: Mitglieder mit mindestens einem Angebot.',
        'options' => [
            'anyone' => 'Alle auf yamo',
            'owners' => 'Eigentümer',
            'connections' => 'Meine Kontakte',
            'owners_and_connections' => 'Eigentümer und meine Kontakte',
        ],
    ],
    'reviews' => [
        'title' => 'Bewertungen',
        'label' => 'Mein Land, meine Region und meine Stadt anzeigen',
        'help' => 'Unter Ihren Bewertungen, neben Ihrem Vornamen.',
    ],
    'data' => [
        'title' => 'Meine Daten',
        'subtitle' => 'Wir erstellen eine Datei Ihrer Daten (JSON) und senden sie an Ihre bestätigte E-Mail-Adresse.',
        'submit' => 'Meine Daten anfordern',
        'sent' => 'Ihre Daten wurden per E-Mail gesendet.',
        'no_verified_email' => 'Bestätigen Sie zuerst Ihre E-Mail-Adresse: Ihre Daten werden nur an eine bestätigte Adresse gesendet.',
        'mail' => [
            'subject' => 'Ihre yamo-Daten',
            'intro' => 'Im Anhang finden Sie die Daten Ihres yamo-Kontos im JSON-Format.',
            'ignore' => 'Wenn Sie diese Anfrage nicht gestellt haben, ändern Sie Ihre E-Mail-Adresse in Ihrem Profil und kontaktieren Sie uns.',
        ],
    ],
];
