<?php

// Identifizierung eines Kontos (Entscheidung 2026-11-25-identification).
return [
    'title' => 'Identität bestätigen',
    'documents' => 'Ausweisdokument',
    'documents_help' => 'Gut lesbare Fotos Ihres Personalausweises, Reisepasses oder Führerscheins (Vorder- und Rückseite), bis zu :max. Nur das Team sieht sie.',
    'add' => 'Fotos hinzufügen',
    'submit' => 'Senden',
    'sent' => 'Danke! Das Team prüft Ihr Ausweisdokument.',
    'not_shared' => 'Wir geben diese Informationen nicht weiter.',
    'country' => 'Ausstellungsland',
    'document_type' => 'Art des Ausweises',
    'document_types' => [
        'driving_licence' => 'Führerschein',
        'passport' => 'Reisepass',
        'identity_card' => 'Personalausweis',
    ],
    'notice' => [
        'missing' => 'Senden Sie ein Ausweisdokument: Sobald das Team es geprüft hat, wird Ihr Profil als identifiziert angezeigt.',
        'pending' => 'Das Team prüft Ihr Ausweisdokument.',
        'identified' => 'Ihr Konto ist seit dem :date identifiziert. Die bestätigten Fotos bleiben; Sie können weitere hinzufügen.',
    ],
    'admin' => [
        'section' => 'Identifizierung',
        'identified_at' => 'Identifizierungsdatum',
        'country' => 'Ausstellungsland',
        'document_type' => 'Art des Ausweises',
        'documents' => 'Ausweisdokumente',
        'no_documents' => 'Kein Dokument gesendet.',
        'validate' => 'Identität bestätigen',
        'validated' => 'Identität bestätigt.',
        'revoke' => 'Identifizierung entfernen',
        'revoked' => 'Identifizierung entfernt.',
        'identified' => 'Identifiziert',
    ],
];
