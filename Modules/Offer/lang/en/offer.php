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
    // Une demande en cours verrouille le statut et le retrait (décision 2026-11-22-logement-verrouille-par-demandes).
    'status_locked' => 'This home has a pending or accepted request: its status cannot change until it is settled or over.',
    'delete_locked' => 'This home has a pending or accepted request: it cannot be removed until it is settled or over.',
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
        'empty' => 'No offers published in this country yet.',
    ],
    'list' => [
        // Le nombre de la liste, sous les filtres (décision 2026-11-22-listes-nombre-en-tete).
        'count' => '{0} No homes|{1} :count home|[2,*] :count homes',
        'empty' => 'Nothing yet.',
        'create' => 'Add',
        'confirm' => 'Remove this offer?',
        'remove' => 'Remove',
        'removed' => 'Offer removed.',
        'expires' => 'Paid until :date',
        'expired' => 'Payment ended on :date',
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
    'expires_at' => 'Paid until',
    'expires_help' => 'If payment is applied (Application page), the offer is no longer visible to others after this date; empty: not visible.',
    'reviews' => 'Reviews',
    // Onglets de l'offre dans le back-office (décision 2026-11-19-ombre-coeur-onglets-offre).
    'tabs' => [
        'listing' => 'General',
        'price' => 'Price',
        'rules' => 'Rules',
        'informations' => 'Information',
        'location' => 'Location',
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
    'show_address' => 'Show the address',
    'show_address_help' => 'Otherwise, the listing page does not show its location.',
    'phone' => 'Contact phone',
    'email' => 'Contact email',
    'contact_help' => 'Shown to the guest in their booking; if empty, the one of your account.',
    'indication' => 'Directions to the address',
    'indication_placeholder' => 'E.g. behind the market, blue gate',
    // Blocs d'informations (décision 2026-11-19-contrat-offer-informations).
    'informations' => [
        'label' => 'Information',
        'add' => 'Add information',
        'title' => 'Title',
        'title_placeholder' => 'E.g. Arrival, Parking, Pets',
        'description' => 'Description',
        'help' => 'Practical information blocks, each with a title: arrival, parking, house rules…',
    ],
    // Langue de saisie (décision 2026-11-19-contrat-langue-saisie-allemand).
    'content_locale' => [
        'step' => 'Language',
        'label' => 'Writing language',
        'help' => 'Which language are you editing this listing in? A language to translate is prefilled with the original text.',
        'original' => 'original language',
        'translated' => 'translated',
        'to_translate' => 'to translate',
    ],
    // Titres des sections de la fiche, servis avec elles (décision 2026-11-19-sections-construites-laravel).
    'section_titles' => [
        'location' => 'Location',
        'facts' => 'Features',
        'rules' => 'Rules',
        'reviews' => 'Reviews',
    ],
    // Bouton de la barre fixe de sa propre offre (décision 2026-11-19-fiche-bouton-modifier).
    'edit' => 'Edit',
    // Groupes du menu du profil (décision 2026-11-19-contrat-reservations-logements-profil).
    'profile_groups' => [
        'traveler' => 'Traveller',
        'host' => 'Host',
    ],
];
