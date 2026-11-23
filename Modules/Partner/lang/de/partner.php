<?php

// Le module Partner : les partenaires de l'accueil (décision 2026-11-23-module-partenaires).

return [
    'singular' => 'Partner',
    'plural' => 'Partner',
    'sections' => [
        'identity' => 'Partner',
        'content' => 'Was die App zeigt',
        'campaign' => 'Kampagne',
        'contact' => 'Kontakt',
        'stats' => 'Statistik',
    ],
    // Décision 2026-11-24-partenaires-statuts.
    'statuses' => [
        'pending' => 'In Prüfung',
        'published' => 'Veröffentlicht',
        'rejected' => 'Abgelehnt',
        'archived' => 'Archiviert',
    ],
    'fields' => [
        'slogan' => 'Slogan',
        'status' => 'Status',
        'user' => 'Konto',
        'is_admin' => 'Administrator',
        'make_admin' => 'Zum Administrator machen',
        'remove_admin' => 'Administration entziehen',
        'proposed' => 'Aus der App vorgeschlagen',
        'name' => 'Name',
        'country' => 'Land',
        'logo' => 'Logo',
        'description' => 'Beschreibung',
        'url' => 'Link',
        'cta_label' => 'Schaltflächentext',
        'published' => 'Veröffentlicht',
        'starts_at' => 'Beginn',
        'expires_at' => 'Läuft ab am',
        'contact_email' => 'Kontakt-E-Mail',
        'contact_phone' => 'Kontakttelefon',
        'views_count' => 'Aufrufe',
        'clicks_count' => 'Klicks',
    ],
    // « Mes partenaires » dans l'app (décision 2026-11-24-partenaires-par-les-membres).
    'member' => [
        // Les étapes du formulaire de l'app (décision 2026-11-24-etape-presentation).
        'steps' => [
            'presentation' => 'Präsentation',
        ],
        'group' => 'Partner',
        'count' => '{0} Kein Partner|{1} :count Partner|[2,*] :count Partner',
        'mine' => 'Meine Partner',
        'add' => 'Partner hinzufügen',
        'edit' => 'Partner bearbeiten',
        'submit' => 'Zur Prüfung senden',
        'save' => 'Speichern und erneut senden',
        'submitted' => 'Partner gesendet: Das Team prüft ihn vor der Veröffentlichung.',
        'saved' => 'Partner gespeichert: Das Team prüft ihn vor der Veröffentlichung erneut.',
        'review_notice' => 'Das Team prüft jeden Partner und jede Änderung, bevor er in der App erscheint.',
        'cta_help' => 'Der Text der Schaltfläche zu Ihrem Link. Leer: « Website besuchen ».',
        'empty' => 'Noch kein Partner.',
        'remove' => 'Entfernen',
        'remove_confirm' => 'Diesen Partner entfernen?',
        'removed' => 'Partner entfernt.',
        'status' => [
            'shown' => 'Angezeigt',
            'hidden' => 'Nicht angezeigt',
        ],
    ],
    // Les comptes qui administrent un partenaire, au back-office.
    'admins' => 'Administratoren',
    // Un changement de statut, aux comptes du partenaire (décision 2026-11-24-partenaires-notifications).
    'notify' => [
        'greeting' => 'Hallo :name,',
        'salutation' => 'Das Team von :app',
        'title' => [
            'pending' => 'Partner in Prüfung',
            'published' => 'Partner veröffentlicht',
            'rejected' => 'Partner abgelehnt',
            'archived' => 'Partner archiviert',
        ],
        'body' => [
            'pending' => 'Ihr Partner « :name » ist beim Team in Prüfung.',
            'published' => 'Ihr Partner « :name » ist veröffentlicht.',
            'rejected' => 'Ihr Partner « :name » wurde vom Team abgelehnt.',
            'archived' => 'Ihr Partner « :name » ist archiviert.',
        ],
    ],
    // La langue de saisie à la modification (décision 2026-11-24-mes-partenaires-cartes).
    'content_locale' => [
        'step' => 'Sprache',
        'label' => 'Eingabesprache',
        'help' => 'In welcher Sprache bearbeiten Sie diesen Partner? Eine zu übersetzende Sprache wird mit einem vorhandenen Text vorausgefüllt.',
        'translated' => 'übersetzt',
        'to_translate' => 'zu übersetzen',
    ],
];
