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
        'awaiting_payment' => 'Zahlung ausstehend',
        'published' => 'Online',
        'rejected' => 'Abgelehnt',
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
        // L'aide du champ logo : la conversion `list` en minimum (décisions 2026-11-24-logo-taille-minimale et 2026-11-24-partenaire-logo-original).
        'logo_help' => 'Quadratisches Logo, mindestens :pixels × :pixels px.',
        'description' => 'Beschreibung',
        'website' => 'Link',
        'cta_label' => 'Schaltflächentext',
        'published' => 'Veröffentlicht',
        'starts_at' => 'Beginn',
        'expires_at' => 'Läuft ab am',
        'featured_until' => 'Hervorgehoben bis',
        'featured_help' => 'Leer: nicht hervorgehoben. Hervorgehoben steht er vor den anderen Partnern, auf der Startseite und auf der Partnerseite.',
        'contact_email' => 'Kontakt-E-Mail',
        'contact_phone' => 'Kontakttelefon',
        'views_count' => 'Aufrufe',
        'clicks_count' => 'Klicks',
    ],
    // « Mes partenaires » dans l'app (décision 2026-11-24-partenaires-par-les-membres).
    'member' => [
        // La barre fixe de « Partenaires » et « Mes partenaires » (décision 2026-11-24-devenir-partenaire).
        'become' => 'Partner werden',
        'add' => 'Partner hinzufügen',
        // Le bouton du champ logo (décision 2026-11-24-champ-fichier-libelle-ajout).
        'add_logo' => 'Logo hinzufügen',
        // Les étapes du formulaire de l'app (décision 2026-11-24-etape-presentation).
        'steps' => [
            'presentation' => 'Präsentation',
        ],
        'group' => 'Partner',
        'count' => '{0} Kein Partner|{1} :count Partner|[2,*] :count Partner',
        // Le nombre de vues, sur les cartes du propriétaire (décision 2026-11-25-compteurs-offres).
        'views' => '{0} Keine Aufrufe|{1} :count Aufruf|[2,*] :count Aufrufe',
        'mine' => 'Meine Partner',
        'add' => 'Partner hinzufügen',
        'edit' => 'Partner bearbeiten',
        'submit' => 'Zur Prüfung senden',
        'save' => 'Speichern',
        'submitted' => 'Partner gesendet: Das Team prüft ihn vor der Veröffentlichung.',
        'saved' => 'Partner gespeichert: Das Team prüft ihn vor der Veröffentlichung erneut.',
        // La note du formulaire, à la création seulement ; le paiement s'il est appliqué (décision 2026-11-24-partenaire-note-creation).
        'review_notice' => 'Wir prüfen zuerst Ihre Angaben, bevor wir diesen Partner in der App anzeigen.',
        'review_notice_paid' => 'Wir prüfen zuerst Ihre Angaben und bestätigen die Zahlung, bevor wir diesen Partner in der App anzeigen.',
        // La note du formulaire à la modification, selon le statut (décision 2026-11-24-partenaire-attente-paiement).
        'edit_notice' => [
            'pending' => 'Dieser Partner wird noch geprüft und wird in der App nicht angezeigt.',
            'rejected' => 'Dieser Partner wurde abgelehnt und wird in der App nicht angezeigt.',
            'awaiting_payment' => 'Für diesen Partner steht die Zahlung noch aus; er wird in der App nicht angezeigt.',
            'shown' => 'Dieser Partner wird in der App angezeigt.',
            'shown_until' => 'Dieser Partner wird bis zum :date in der App angezeigt.',
        ],
        'cta_help' => 'Der Text der Schaltfläche zu Ihrem Link. Leer: « Website besuchen ».',
        'empty' => 'Noch kein Partner.',
        'remove' => 'Entfernen',
        'history' => 'Nachrichten',
        'remove_confirm' => 'Diesen Partner entfernen?',
        'removed' => 'Partner entfernt.',
        'status' => [
            'shown' => 'Online',
            'hidden' => 'Nicht angezeigt',
        ],
    ],
    // Les comptes qui administrent un partenaire, au back-office.
    'admins' => 'Administratoren',
    // « Valider » au back-office (décision 2026-11-24-partenaires-validation-back-office).
    'validate' => [
        'label' => 'Freigeben',
        'heading' => 'Diesen Partner freigeben?',
        'description' => 'Er wird « Veröffentlicht » und seine Konten werden benachrichtigt.',
        'done' => 'Partner angenommen.',
    ],
    // Un changement de statut, aux comptes du partenaire (décision 2026-11-24-partenaires-notifications).
    'message' => [
        'title' => 'Partner-Nachricht',
        'write' => 'Schreiben',
        'field' => 'Nachricht',
        'placeholder' => 'Ihre Nachricht',
        'submit' => 'Senden',
        'sent' => 'Nachricht gesendet.',
        'subject' => 'Neue Nachricht zu „:partner“',
        'intro' => ':name hat zu „:partner“ geschrieben:',
        'open_app' => 'Antworten Sie in der App :app.',
        'sms' => ':app: :name hat zu „:partner“ geschrieben. Öffnen Sie die App, um die Nachricht zu lesen.',
    ],
    'notify' => [
        // Les champs changés d'une modification (décision 2026-11-26-partenaire-modifications).
        'changes' => ['line' => ':field: :value', 'empty' => '(leer)', 'before' => ':field (vorher)', 'after' => ':field (nachher)', 'at' => 'Zuletzt geändert', 'by' => 'Geändert von', 'title' => 'Änderungen'],
        // La liste « Notifications » : le type « Partenaire », puis un type par statut, ses statuts dans le filtre « Statut » (décision 2026-11-24-notifications-types-groupes-statut).
        'filter_type' => 'Partner',
        'greeting' => 'Hallo :name,',
        'salutation' => 'Das Team von :app',
        'title' => [
            'pending' => 'Partner in Prüfung',
            'awaiting_payment' => 'Partner: Zahlung ausstehend',
            'published' => 'Partner angenommen',
            'rejected' => 'Partner abgelehnt',
        ],
        'body' => [
            'pending' => 'Ihr Partner « :name » ist beim Team in Prüfung.',
            'awaiting_payment' => 'Für Ihren Partner „:name“ steht die Zahlung noch aus.',
            'published' => 'Ihr Partner « :name » wurde vom Team angenommen.',
            'rejected' => 'Ihr Partner « :name » wurde vom Team abgelehnt.',
        ],
        // Ajouté ou modifié au back-office, aux administrateurs du partenaire (décision 2026-11-24-partenaire-demandes).
        'team' => [
            'created' => 'Ihr Partner „:name“ wurde vom Team hinzugefügt.',
            'updated' => 'Ihr Partner „:name“ wurde vom Team geändert.',
        ],
        'submitted' => [
            'title' => ['created' => 'Partner hinzugefügt', 'updated' => 'Partner geändert'],
            'own' => [
                'created' => 'Ihr Partner « :name » ist in Prüfung: Das Team prüft ihn, bevor er angezeigt wird.',
                'updated' => 'Ihr Partner „:name“ wurde geändert.',
            ],
            'others' => [
                'created' => '« :name » wurde von :actor hinzugefügt; er ist in Prüfung.',
                // Pour les destinataires : le partenaire, sans qui l'a modifié, nommé au-dessus sur la carte (décision
                // 2026-11-26-partenaire-modifie-destinataires).
                'updated' => 'Der Partner „:name“ wurde geändert.',
            ],
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
    // La commande partner:await-payment (décision 2026-11-24-partenaire-attente-paiement-commande).
    'await_payment' => [
        'disabled' => 'Die Zahlung für Partner ist nicht aktiviert: nichts zu tun.',
        'count' => '{0} Kein Partner auf „Zahlung ausstehend“ gesetzt.|{1} :count Partner auf „Zahlung ausstehend“ gesetzt.|[2,*] :count Partner auf „Zahlung ausstehend“ gesetzt.',
    ],
];
