<?php

// Domaine « location de logements » (décision 2026-11-18-offres-et-domaines).

return [
    'singular' => 'Logement',
    'plural' => 'Logements',
    'mine' => 'Mes logements',
    'add' => 'Ajouter un logement',
    'request' => 'Demande de logement',
    'property_type' => 'Type de logement',
    // Type de location (décision 2026-11-20-contrat-type-de-location).
    'rental_type' => 'Type de location',
    'rental_types' => [
        'long_term' => 'Location',
        'furnished' => 'Location meublée',
        'shared' => 'Colocation',
    ],
    'property_types' => [
        'apartment' => 'Appartement',
        'house' => 'Maison',
        'studio' => 'Studio',
        'room' => 'Chambre',
    ],
    // Titre dans les listes : « meublé » accordé au type, entre le type et la ville.
    'furnished_words' => [
        'apartment' => 'meublé',
        'house' => 'meublée',
        'studio' => 'meublé',
        'room' => 'meublée',
    ],
    // Unité du loyer : par jour pour un meublé, par mois sinon.
    'price_units' => [
        'day' => 'jour',
        'month' => 'mois',
    ],
    // Sous-titre des listes : le nombre de chambres, s'il y en a plus d'une.
    'furnished' => 'Meublé',
    'surface' => 'Surface',
    'rooms' => 'Pièces',
    'rooms_help' => 'Les demi-pièces sont admises (3,5).',
    'bedrooms' => 'Chambres',
    'available_from' => 'Disponible dès le',
    'charges' => 'Charges mensuelles',
    'charges_help' => 'En plus du loyer.',
    'deposit_months' => 'Caution',
    'advance_months' => 'Avance de loyer',
    'months' => 'mois',
    'deposit' => 'Caution',
    'price' => 'Loyer',
    // Étapes du formulaire de l'app (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
    'steps' => [
        'rules' => 'Règles',
        'informations' => 'Informations',
        'photos' => 'Photos',
        'presentation' => 'Présentation',
        'price' => 'Prix et disponibilité',
        'place' => 'Localisation',
        'features' => 'Caractéristiques',
    ],
    // Filtres de la vitrine (décision 2026-11-19-contrat-filtres-vitrine).
    'filters' => [
        'amenities_help' => 'Les logements qui ont tous les équipements choisis.',
        'any' => 'Tous',
        'furnished' => 'Oui',
        'unfurnished' => 'Non',
        'bedrooms' => 'Chambres',
        'available_from' => 'Libre le',
        'available_from_help' => 'Les logements disponibles à cette date ou avant.',
    ],
    // Équipements (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
    'amenities_label' => 'Équipements',
    'amenities' => [
        'wifi' => 'Wifi',
        'parking' => 'Parking',
        'air_conditioning' => 'Climatisation',
        'hot_water' => 'Eau chaude',
        'generator' => 'Groupe électrogène',
        'water_tank' => "Réserve d'eau / forage",
        'security' => 'Gardien / sécurité',
        'equipped_kitchen' => 'Cuisine équipée',
        'washing_machine' => 'Lave-linge',
        'iron' => 'Fer à repasser',
        'tv' => 'Télévision',
        'balcony' => 'Balcon / terrasse',
        'pool' => 'Piscine',
    ],
    // Règles et informations pratiques (décision 2026-11-19-contrat-regles-informations).
    'rules' => [
        // Les heures d'un meublé (décision 2026-11-20-heures-arrivee-depart).
        'check_in_from' => 'Arrivée à partir de',
        'check_out_until' => 'Départ au plus tard',
        'documents' => 'Documents demandés',
        'document_options' => [
            'id_card' => "Pièce d'identité",
            'passport' => 'Passeport',
            'residence_permit' => 'Titre de séjour',
            'proof_of_income' => 'Justificatif de revenus',
            'guarantor' => 'Garant',
            'deposit' => 'Dépôt de garantie',
        ],
        'pets' => 'Animaux acceptés',
        'smoking' => 'Fumeurs acceptés',
        'parties' => 'Fêtes et événements autorisés',
        'yes' => 'Oui',
        'no' => 'Non',
    ],
];
