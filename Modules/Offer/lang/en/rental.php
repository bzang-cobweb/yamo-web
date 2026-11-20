<?php

// "Housing rental" domain (decision 2026-11-18-offres-et-domaines).

return [
    'singular' => 'Home',
    'plural' => 'Homes',
    'mine' => 'My homes',
    'add' => 'Add a home',
    'request' => 'Home request',
    'property_type' => 'Property type',
    // Type de location (décision 2026-11-20-contrat-type-de-location).
    'rental_type' => 'Rental type',
    'rental_types' => [
        'long_term' => 'Long-term rental',
        'furnished' => 'Furnished rental',
        'shared' => 'Flat share',
    ],
    'property_types' => [
        'apartment' => 'Apartment',
        'house' => 'House',
        'studio' => 'Studio',
        'room' => 'Room',
    ],
    // Titre dans les listes : « meublé » accordé au type, entre le type et la ville.
    'furnished_words' => [
        'apartment' => 'furnished',
        'house' => 'furnished',
        'studio' => 'furnished',
        'room' => 'furnished',
    ],
    // Unité du loyer : par jour pour un meublé, par mois sinon.
    'price_units' => [
        'day' => 'day',
        'month' => 'month',
    ],
    // Sous-titre des listes : le nombre de chambres, s'il y en a plus d'une.
    'furnished' => 'Furnished',
    'surface' => 'Area',
    'rooms' => 'Rooms',
    'rooms_help' => 'Half rooms are allowed (3.5).',
    'bedrooms' => 'Bedrooms',
    'available_from' => 'Available from',
    'charges' => 'Monthly charges',
    'charges_help' => 'On top of the rent.',
    'price' => 'Rent',
    // Étapes du formulaire de l'app (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
    'steps' => [
        'rules' => 'Rules',
        'informations' => 'Information',
        'photos' => 'Photos',
        'presentation' => 'Presentation',
        'price' => 'Price and availability',
        'place' => 'Location',
        'features' => 'Features',
    ],
    // Filtres de la vitrine (décision 2026-11-19-contrat-filtres-vitrine).
    'filters' => [
        'amenities_help' => 'Homes that have all the chosen amenities.',
        'any' => 'All',
        'furnished' => 'Yes',
        'unfurnished' => 'No',
        'bedrooms' => 'Bedrooms',
        'available_from' => 'Free on',
        'available_from_help' => 'Homes available on this date or earlier.',
    ],
    // Équipements (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
    'amenities_label' => 'Amenities',
    'amenities' => [
        'wifi' => 'Wi-Fi',
        'parking' => 'Parking',
        'air_conditioning' => 'Air conditioning',
        'hot_water' => 'Hot water',
        'generator' => 'Generator',
        'water_tank' => 'Water tank / borehole',
        'security' => 'Guard / security',
        'equipped_kitchen' => 'Equipped kitchen',
        'washing_machine' => 'Washing machine',
        'iron' => 'Iron',
        'tv' => 'Television',
        'balcony' => 'Balcony / terrace',
        'pool' => 'Swimming pool',
    ],
    // Règles et informations pratiques (décision 2026-11-19-contrat-regles-informations).
    'rules' => [
        'documents' => 'Required documents',
        'document_options' => [
            'id_card' => 'ID card',
            'passport' => 'Passport',
            'residence_permit' => 'Residence permit',
            'proof_of_income' => 'Proof of income',
            'guarantor' => 'Guarantor',
            'deposit' => 'Security deposit',
        ],
        'pets' => 'Pets allowed',
        'smoking' => 'Smoking allowed',
        'parties' => 'Parties and events allowed',
        'yes' => 'Yes',
        'no' => 'No',
    ],
];
