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
    // Titre dans les listes : le type, « meublé » accordé, la ville.
    'furnished_types' => [
        'apartment' => 'Furnished apartment',
        'house' => 'Furnished house',
        'studio' => 'Furnished studio',
        'room' => 'Furnished room',
    ],
    // Unité du loyer : par jour pour un meublé, par mois sinon.
    'price_units' => [
        'day' => 'day',
        'month' => 'month',
    ],
    'title_in_city' => ':type - :city',
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
