<?php

// Domaine « location de logements » (décision 2026-11-18-offres-et-domaines).

return [
    'singular' => 'Logement',
    'plural' => 'Logements',
    'mine' => 'Mes logements',
    'add' => 'Ajouter un logement',
    'property_type' => 'Type de logement',
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
    'price' => 'Loyer',
    // Étapes du formulaire de l'app (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
    'steps' => [
        'home' => 'Le logement',
        'price' => 'Prix et disponibilité',
        'place' => 'Adresse et catégorie',
        'listing' => "L'annonce",
    ],
];
