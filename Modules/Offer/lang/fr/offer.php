<?php

// Offres, communes à tous les domaines (décision 2026-11-18-offres-et-domaines).

return [
    'singular' => 'offre',
    'plural' => 'offres',
    'title' => 'Offres',
    'navigation_group' => 'Offres',
    'owner' => 'Propriétaire',
    'title_field' => 'Titre',
    'description' => 'Description',
    'category' => 'Catégorie',
    'country' => 'Pays',
    'price_currency_help' => "Dans la devise du pays de l'offre.",
    'price' => 'Prix',
    'currency' => 'Devise',
    'address' => 'Adresse',
    'address_country' => "L'adresse doit être dans le pays de l'offre.",
    'address_help' => 'Choisie parmi les adresses de votre compte.',
    'status' => 'Statut',
    'published_at' => 'Publiée le',
    'photos' => 'Photos',
    'photos_help' => 'La première est la photo principale. JPEG, PNG ou WebP.',
    'attributes' => 'Caractéristiques',
    'statuses' => [
        'draft' => 'Brouillon',
        'published' => 'Publiée',
        'archived' => 'Archivée',
    ],
    'one_per_account' => 'Votre compte a déjà une offre.',
    'category_resource' => [
        'singular' => 'catégorie',
        'plural' => 'catégories',
        'name' => 'Nom',
        'slug' => 'Identifiant',
        'position' => 'Position',
        'offers_count' => 'Offres',
        'parent' => 'Catégorie parente',
        'parent_none' => 'Aucune (catégorie racine)',
        'children' => 'Sous-catégories',
        'cycle' => "Une catégorie ne peut pas être rangée sous elle-même ni sous l'une de ses sous-catégories.",
        'has_children' => "Cette catégorie a des sous-catégories : supprimez-les ou déplacez-les d'abord.",
    ],
    'form' => [
        'create_title' => 'Ajouter : :label',
        'edit_title' => 'Modifier : :label',
        'save' => 'Enregistrer',
        'saved' => 'Enregistré.',
    ],
    'browse' => [
        'search' => 'Rechercher par titre ou ville…',
        'empty' => "Aucune offre publiée dans ce pays pour l'instant.",
    ],
    'list' => [
        'empty' => 'Rien pour l\'instant.',
        'search' => 'Rechercher par titre…',
        'create' => 'Ajouter',
        'confirm' => 'Retirer cette offre ?',
        'remove' => 'Retirer',
        'removed' => 'Offre retirée.',
        'expires' => "Payé jusqu'au :date",
        'expired' => 'Paiement échu le :date',
    ],
    // Vedette et favoris (décision 2026-11-19-contrat-vedette-favoris).
    'featured_until' => "En vedette jusqu'au",
    'featured' => 'En vedette',
    'featured_help' => 'Vide : pas en vedette. Plus tard, une mise en avant payée par le propriétaire remplira cette date.',
    'favorites' => [
        'title' => 'Mes favoris',
        'empty' => "Aucun favori pour l'instant. Touchez le cœur d'une offre pour la retrouver ici.",
        'own_offer' => 'Vous ne pouvez pas mettre votre propre offre en favori.',
    ],
    // Section « Accès » du back-office (décision 2026-11-19-acces-avis-backoffice).
    'access' => 'Accès',
    'expires_at' => "Payé jusqu'au",
    'expires_help' => "Si le paiement est appliqué (page Application), l'offre n'est plus visible des autres après cette date ; vide : pas visible.",
    'reviews' => 'Avis',
    // Onglets de l'offre dans le back-office (décision 2026-11-19-ombre-coeur-onglets-offre).
    'tabs' => [
        'listing' => 'Général',
        'price' => 'Prix',
        'rules' => 'Règles',
        'informations' => 'Informations',
        'location' => 'Localisation',
    ],
    // Filtres de la vitrine (décision 2026-11-19-contrat-filtres-vitrine).
    'filters' => [
        'city' => 'Ville',
    ],
    // Adresse saisie (décision 2026-11-19-contrat-adresse-offre-recherche).
    'street' => 'Rue',
    'street_help' => 'Tapez au moins 3 caractères : les adresses connues sont proposées.',
    'zip' => 'Code postal',
    'city' => 'Ville',
    // Indications sur l'adresse (décision 2026-11-19-description-adresse-offre).
    'indication' => "Indications sur l'adresse",
    'indication_placeholder' => 'Ex. : derrière le marché, portail bleu',
    // Blocs d'informations (décision 2026-11-19-contrat-offer-informations).
    'informations' => [
        'label' => 'Informations',
        'add' => 'Ajouter une information',
        'title' => 'Titre',
        'title_placeholder' => 'Ex. : Arrivée, Parking, Animaux',
        'description' => 'Description',
        'help' => "Des blocs d'informations pratiques, chacun avec un titre : arrivée, parking, règles de la maison…",
    ],
    // Langue de saisie (décision 2026-11-19-contrat-langue-saisie-allemand).
    'content_locale' => [
        'step' => 'Langue',
        'label' => 'Langue de saisie',
        'help' => "Dans quelle langue modifiez-vous cette annonce ? Une langue à traduire est pré-remplie avec le texte d'origine.",
        'original' => "langue d'origine",
        'translated' => 'traduite',
        'to_translate' => 'à traduire',
    ],
];
