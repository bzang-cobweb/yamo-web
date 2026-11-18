<?php

// "Housing rental" domain (decision 2026-11-18-offres-et-domaines).

return [
    'singular' => 'Home',
    'plural' => 'Homes',
    'mine' => 'My homes',
    'add' => 'Add a home',
    'property_type' => 'Property type',
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
    'bedrooms_count' => ':count bedroom|:count bedrooms',
    'furnished' => 'Furnished',
    'surface' => 'Area',
    'rooms' => 'Rooms',
    'rooms_help' => 'Half rooms are allowed (3.5).',
    'bedrooms' => 'Bedrooms',
    'available_from' => 'Available from',
    'charges' => 'Monthly charges',
    'charges_help' => 'On top of the rent.',
    'price' => 'Rent',
];
