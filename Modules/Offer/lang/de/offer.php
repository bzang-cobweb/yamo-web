<?php

// Offers, common to every domain (decision 2026-11-18-offres-et-domaines).

return [
    'singular' => 'Angebot',
    'plural' => 'Angebote',
    'title' => 'Angebote',
    'navigation_group' => 'Angebote',
    'owner' => 'Eigentümer',
    'title_field' => 'Titel',
    'description' => 'Beschreibung',
    'category' => 'Kategorie',
    'country' => 'Land',
    'price_currency_help' => 'In der Währung des Landes des Angebots.',
    'price' => 'Preis',
    'currency' => 'Währung',
    'address' => 'Adresse',
    'address_country' => 'Die Adresse muss im Land des Angebots liegen.',
    'address_help' => 'Aus den Adressen Ihres Kontos gewählt.',
    'status' => 'Status',
    // Une demande en cours verrouille le statut et le retrait (décision 2026-11-22-logement-verrouille-par-demandes).
    'status_locked' => 'Diese Unterkunft hat eine ausstehende oder angenommene Anfrage: ihr Status kann erst geändert werden, wenn sie erledigt oder beendet ist.',
    'delete_locked' => 'Diese Unterkunft hat eine ausstehende oder angenommene Anfrage: sie kann erst entfernt werden, wenn sie erledigt oder beendet ist.',
    'published_at' => 'Veröffentlicht am',
    'photos' => 'Fotos',
    'photos_help' => 'Das erste ist das Hauptfoto. JPEG, PNG oder WebP.',
    'attributes' => 'Merkmale',
    'statuses' => [
        'draft' => 'Entwurf',
        'published' => 'Veröffentlicht',
        'archived' => 'Archiviert',
    ],
    'one_per_account' => 'Ihr Konto hat bereits ein Angebot.',
    'category_resource' => [
        'singular' => 'Kategorie',
        'plural' => 'Kategorien',
        'name' => 'Name',
        'slug' => 'Kennung',
        'position' => 'Position',
        'offers_count' => 'Angebote',
        'parent' => 'Übergeordnete Kategorie',
        'parent_none' => 'Keine (Hauptkategorie)',
        'children' => 'Unterkategorien',
        'cycle' => 'Eine Kategorie kann nicht unter sich selbst oder eine ihrer Unterkategorien gestellt werden.',
        'has_children' => 'Diese Kategorie hat Unterkategorien: Löschen oder verschieben Sie diese zuerst.',
    ],
    'form' => [
        'create_title' => 'Hinzufügen: :label',
        'edit_title' => 'Bearbeiten: :label',
        'save' => 'Speichern',
        'saved' => 'Gespeichert.',
    ],
    'browse' => [
        'empty' => 'In diesem Land wurde noch kein Angebot veröffentlicht.',
    ],
    'list' => [
        // Le nombre de la liste, sous les filtres (décision 2026-11-22-listes-nombre-en-tete).
        'count' => '{0} Keine Unterkünfte|{1} :count Unterkunft|[2,*] :count Unterkünfte',
        'empty' => 'Noch nichts.',
        'create' => 'Hinzufügen',
        'confirm' => 'Dieses Angebot entfernen?',
        'remove' => 'Entfernen',
        'removed' => 'Angebot entfernt.',
        'starts' => 'Bezahlt ab :date',
        'expires' => 'Bezahlt bis :date',
        'expired' => 'Bezahlung endete am :date',
        // Publier, dépublier (décision 2026-11-23-publier-depublier-menu).
        'publish' => 'Veröffentlichen',
        'published' => 'Unterkunft veröffentlicht.',
        'unpublish' => 'Zurückziehen',
        'unpublished' => 'Unterkunft wieder als Entwurf gespeichert.',
    ],
    // Vedette et favoris (décision 2026-11-19-contrat-vedette-favoris).
    'featured_until' => 'Hervorgehoben bis',
    'featured' => 'Hervorgehoben',
    'featured_help' => 'Leer: nicht hervorgehoben. Später füllt eine vom Eigentümer bezahlte Werbung dieses Datum.',
    'favorites' => [
        'title' => 'Meine Favoriten',
        'empty' => 'Noch keine Favoriten. Tippen Sie auf das Herz eines Angebots, um es hier wiederzufinden.',
        'own_offer' => 'Sie können Ihr eigenes Angebot nicht zu Ihren Favoriten hinzufügen.',
    ],
    // Section « Accès » du back-office (décision 2026-11-19-acces-avis-backoffice).
    'access' => 'Zugang',
    // Décision 2026-11-23-offre-paye-a-partir-du.
    'starts_at' => 'Bezahlt ab',
    'starts_help' => 'Wenn die Zahlung angewendet wird (Seite Anwendung), ist das Angebot erst ab diesem Datum für andere sichtbar; leer: sofort.',
    'expires_at' => 'Bezahlt bis',
    'expires_help' => 'Wenn die Zahlung angewendet wird (Seite Anwendung), ist das Angebot nach diesem Datum für andere nicht mehr sichtbar; leer: nicht sichtbar.',
    'reviews' => 'Bewertungen',
    // Onglets de l'offre dans le back-office (décision 2026-11-19-ombre-coeur-onglets-offre).
    'tabs' => [
        'listing' => 'Allgemein',
        'price' => 'Preis',
        'rules' => 'Regeln',
        'informations' => 'Informationen',
        'location' => 'Lage',
    ],
    // Filtres de la vitrine (décision 2026-11-19-contrat-filtres-vitrine).
    'filters' => [
        'city' => 'Stadt',
    ],
    // Adresse saisie (décision 2026-11-19-contrat-adresse-offre-recherche).
    'street' => 'Straße',
    'street_help' => 'Geben Sie mindestens 3 Zeichen ein: Bekannte Adressen werden vorgeschlagen.',
    'zip' => 'Postleitzahl',
    'city' => 'Stadt',
    // Indications sur l'adresse (décision 2026-11-19-description-adresse-offre).
    'show_address' => 'Adresse anzeigen',
    'show_address_help' => 'Sonst zeigt die Seite der Unterkunft ihren Standort nicht.',
    'phone' => 'Kontakttelefon',
    'email' => 'Kontakt-E-Mail',
    'contact_help' => 'Wird dem Gast in seiner Buchung angezeigt; leer, die Ihres Kontos.',
    'indication' => 'Wegbeschreibung',
    'indication_placeholder' => 'Z. B. hinter dem Markt, blaues Tor',
    // Blocs d'informations (décision 2026-11-19-contrat-offer-informations).
    'informations' => [
        'label' => 'Informationen',
        'add' => 'Information hinzufügen',
        'title' => 'Titel',
        'title_placeholder' => 'Z. B. Anreise, Parken, Haustiere',
        'description' => 'Beschreibung',
        'help' => 'Blöcke mit praktischen Informationen, jeder mit einem Titel: Anreise, Parken, Hausregeln…',
    ],
    // Langue de saisie (décision 2026-11-19-contrat-langue-saisie-allemand).
    'content_locale' => [
        'step' => 'Sprache',
        'label' => 'Sprache der Eingabe',
        'help' => 'In welcher Sprache bearbeiten Sie dieses Inserat? Eine zu übersetzende Sprache ist mit dem Originaltext vorausgefüllt.',
        'original' => 'Originalsprache',
        'translated' => 'übersetzt',
        'to_translate' => 'zu übersetzen',
    ],
    // Titres des sections de la fiche, servis avec elles (décision 2026-11-19-sections-construites-laravel).
    'section_titles' => [
        'description' => 'Beschreibung',
        'location' => 'Lage',
        'facts' => 'Merkmale',
        'rules' => 'Regeln',
        'reviews' => 'Bewertungen',
    ],
    // Bouton de la barre fixe de sa propre offre (décision 2026-11-19-fiche-bouton-modifier).
    'edit' => 'Bearbeiten',
    // Groupes du menu du profil (décision 2026-11-19-contrat-reservations-logements-profil).
    'profile_groups' => [
        'traveler' => 'Reisende',
        'host' => 'Gastgeber',
    ],
];
