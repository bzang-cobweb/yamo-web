<?php

// "Housing rental" domain (decision 2026-11-18-offres-et-domaines).

return [
    'singular' => 'Unterkunft',
    'plural' => 'Unterkünfte',
    'mine' => 'Meine Unterkünfte',
    'add' => 'Unterkunft hinzufügen',
    'request' => 'Unterkunftsanfrage',
    'property_type' => 'Art der Unterkunft',
    // Type de location (décision 2026-11-20-contrat-type-de-location).
    'rental_type' => 'Art der Vermietung',
    'rental_types' => [
        'long_term' => 'Langzeitmiete',
        'furnished' => 'Möblierte Vermietung',
        'shared' => 'Wohngemeinschaft',
    ],
    'property_types' => [
        'apartment' => 'Wohnung',
        'house' => 'Haus',
        'studio' => 'Studio',
        'room' => 'Zimmer',
    ],
    // Titre dans les listes : « meublé » accordé au type, entre le type et la ville.
    'furnished_words' => [
        'apartment' => 'möbliert',
        'house' => 'möbliert',
        'studio' => 'möbliert',
        'room' => 'möbliert',
    ],
    // Unité du loyer : par jour pour un meublé, par mois sinon.
    'price_units' => [
        'day' => 'Tag',
        'month' => 'Monat',
    ],
    // Sous-titre des listes : le nombre de chambres, s'il y en a plus d'une.
    'furnished' => 'Möbliert',
    'surface' => 'Fläche',
    'rooms' => 'Zimmer',
    'rooms_help' => 'Halbe Zimmer sind erlaubt (3,5).',
    'bedrooms' => 'Schlafzimmer',
    'available_from' => 'Verfügbar ab',
    'charges' => 'Monatliche Nebenkosten',
    'charges_help' => 'Zusätzlich zur Miete.',
    'price' => 'Miete',
    // Étapes du formulaire de l'app (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
    'steps' => [
        'rules' => 'Regeln',
        'informations' => 'Informationen',
        'photos' => 'Fotos',
        'presentation' => 'Vorstellung',
        'price' => 'Preis und Verfügbarkeit',
        'place' => 'Lage',
        'features' => 'Merkmale',
    ],
    // Filtres de la vitrine (décision 2026-11-19-contrat-filtres-vitrine).
    'filters' => [
        'amenities_help' => 'Unterkünfte mit allen gewählten Ausstattungen.',
        'any' => 'Alle',
        'furnished' => 'Ja',
        'unfurnished' => 'Nein',
        'bedrooms' => 'Schlafzimmer',
        'available_from' => 'Frei am',
        'available_from_help' => 'Unterkünfte, die an diesem Datum oder früher verfügbar sind.',
    ],
    // Équipements (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
    'amenities_label' => 'Ausstattung',
    'amenities' => [
        'wifi' => 'WLAN',
        'parking' => 'Parkplatz',
        'air_conditioning' => 'Klimaanlage',
        'hot_water' => 'Warmwasser',
        'generator' => 'Stromgenerator',
        'water_tank' => 'Wassertank / Brunnen',
        'security' => 'Wachdienst / Sicherheit',
        'equipped_kitchen' => 'Ausgestattete Küche',
        'washing_machine' => 'Waschmaschine',
        'iron' => 'Bügeleisen',
        'tv' => 'Fernseher',
        'balcony' => 'Balkon / Terrasse',
        'pool' => 'Schwimmbad',
    ],
    // Règles et informations pratiques (décision 2026-11-19-contrat-regles-informations).
    'rules' => [
        'documents' => 'Erforderliche Dokumente',
        'document_options' => [
            'id_card' => 'Personalausweis',
            'passport' => 'Reisepass',
            'residence_permit' => 'Aufenthaltstitel',
            'proof_of_income' => 'Einkommensnachweis',
            'guarantor' => 'Bürge',
            'deposit' => 'Kaution',
        ],
        'pets' => 'Haustiere erlaubt',
        'smoking' => 'Rauchen erlaubt',
        'parties' => 'Partys und Veranstaltungen erlaubt',
        'yes' => 'Ja',
        'no' => 'Nein',
    ],
];
