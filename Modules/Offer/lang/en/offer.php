<?php

// Offers, common to every domain (decision 2026-11-18-offres-et-domaines).

return [
    'singular' => 'offer',
    'plural' => 'offers',
    'title' => 'Offers',
    'navigation_group' => 'Offers',
    'owner' => 'Owner',
    'title_field' => 'Title',
    'description' => 'Description',
    'category' => 'Category',
    'country' => 'Country',
    'price_currency_help' => "In the currency of the offer's country.",
    'price' => 'Price',
    'currency' => 'Currency',
    'address' => 'Address',
    'address_country' => "The address must be in the offer's country.",
    'address_help' => 'Chosen among your account addresses.',
    'status' => 'Status',
    'published_at' => 'Published on',
    'photos' => 'Photos',
    'photos_help' => 'The first one is the main photo. JPEG, PNG or WebP.',
    'attributes' => 'Features',
    'statuses' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],
    'one_per_account' => 'Your account already has an offer.',
    'category_resource' => [
        'singular' => 'category',
        'plural' => 'categories',
        'name' => 'Name',
        'slug' => 'Identifier',
        'position' => 'Position',
        'offers_count' => 'Offers',
        'parent' => 'Parent category',
        'parent_none' => 'None (root category)',
        'children' => 'Subcategories',
        'cycle' => 'A category cannot be placed under itself or one of its subcategories.',
        'has_children' => 'This category has subcategories: delete or move them first.',
    ],
    'form' => [
        'create_title' => 'Add: :label',
        'edit_title' => 'Edit: :label',
        'save' => 'Save',
        'saved' => 'Saved.',
    ],
    'browse' => [
        'search' => 'Search by title or city…',
        'empty' => 'No offers published in this country yet.',
    ],
    'list' => [
        'empty' => 'Nothing yet.',
        'search' => 'Search by title…',
        'create' => 'Add',
        'confirm' => 'Remove this offer?',
        'remove' => 'Remove',
        'removed' => 'Offer removed.',
        'expires' => 'Expires on :date',
        'expired' => 'Expired on :date',
    ],
    // Vedette et favoris (décision 2026-11-19-contrat-vedette-favoris).
    'featured_until' => 'Featured until',
    'featured' => 'Featured',
    'featured_help' => 'Empty: not featured. Later, a promotion paid by the owner will fill this date.',
    'favorites' => [
        'title' => 'My favorites',
        'empty' => 'No favorites yet. Tap the heart of an offer to find it here.',
        'own_offer' => 'You cannot add your own offer to your favorites.',
    ],
    // Section « Accès » du back-office (décision 2026-11-19-acces-avis-backoffice).
    'access' => 'Access',
    'expires_at' => 'Expiration date',
    'expires_help' => 'If payment is applied (Application page), the offer is no longer visible to others after this date; empty: not visible.',
    'reviews' => 'Reviews',
    // Onglets de l'offre dans le back-office (décision 2026-11-19-ombre-coeur-onglets-offre).
    'tabs' => [
        'listing' => 'Listing',
    ],
    // Filtres de la vitrine (décision 2026-11-19-contrat-filtres-vitrine).
    'filters' => [
        'city' => 'City',
    ],
    // Adresse saisie (décision 2026-11-19-contrat-adresse-offre-recherche).
    'street' => 'Street',
    'street_help' => 'Type at least 3 characters: known addresses are suggested.',
    'zip' => 'Postal code',
    'city' => 'City',
    // Indications sur l'adresse (décision 2026-11-19-description-adresse-offre).
    'indication' => 'Directions to the address',
    'indication_placeholder' => 'E.g. behind the market, blue gate',
];
