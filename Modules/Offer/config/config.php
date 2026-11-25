<?php

return [
    'name' => 'Offer',

    /*
     * Domaine du projet (décision 2026-11-18-offres-et-domaines) : clé d'un domaine
     * inscrit dans `DomainRegistry` (`rental`…). Vide : le module n'expose rien.
     * Une clé inconnue est une erreur au démarrage.
     */
    'domain' => env('OFFER_DOMAIN') ?: null,

    /*
     * Nombre des derniers avis servis avec la fiche d'une offre (décision
     * 2026-11-19-contrat-paiement-visibilite-avis) ; l'app propose « Voir tous les
     * avis » quand il y en a davantage.
     */
    'reviews_shown' => 1,

    /*
     * Sections repliables de la fiche d'une offre, dans l'ordre, et ouvertes ou non à
     * l'arrivée (décision 2026-11-19-sections-fiche-laravel) : `location` (adresse et
     * indication), `facts` (caractéristiques), `rules` (règles et autres informations),
     * `reviews` (avis), `informations` (un bloc d'informations par section, décision
     * 2026-11-19-contrat-offer-informations ; l'état vaut pour chacun). Une section
     * sans contenu n'est pas servie ; si aucune des sections servies n'est ouverte,
     * la première l'est.
     */
    // Caractéristiques, localisation, description, puis le reste (décision 2026-11-22-fiche-localisation-apres-caracteristiques) ;
    // seule la première ouverte à l'arrivée (décision 2026-11-22-fiche-premiere-section-ouverte).
    'sections' => [
        'facts' => true,
        'location' => false,
        'description' => false,
        'rules' => false,
        'reviews' => false,
        'informations' => false,
    ],

    // Les photos d'une offre, envoyées depuis l'app ou le back-office (décision 2026-11-24-avatar-photos-offre-conversions).
    'photos' => [
        // Les types de fichier acceptés, séparés par des virgules.
        'mime_types' => array_values(array_filter(array_map('trim', explode(',', (string) env('OFFER_PHOTOS_MIME_TYPES', 'image/jpeg,image/png,image/webp'))))),
        // La taille maximale d'un envoi, en kilo-octets.
        'max_upload_kb' => (int) env('OFFER_PHOTOS_MAX_UPLOAD_KB', 10240),
        // Le plus grand côté permis à l'envoi, en pixels : garde-fou contre une image démesurée à décoder (décision
        // 2026-11-24-image-dimension-maximale).
        'max_dimension' => (int) env('OFFER_PHOTOS_MAX_DIMENSION', 10000),
        // Le nombre maximal de photos d'une offre.
        'max_files' => (int) env('OFFER_PHOTOS_MAX_FILES', 20),
        // La qualité du WebP enregistré, de 1 à 100.
        'quality' => (int) env('OFFER_PHOTOS_QUALITY', 80),
        // Une conversion par endroit où la photo s'affiche : la largeur, en pixels (trois fois l'affichage, pour les
        // écrans haute densité) ; les photos remplissent un cadre 16:9, c'est la largeur qui compte. Proportions
        // gardées, en WebP ; la photo enregistrée prend la plus grande de ces largeurs.
        'conversions' => [
            // Le site web : la galerie de la fiche et son agrandissement (un écran Full HD) ; c'est aussi la largeur
            // de la photo enregistrée, l'original n'étant pas gardé.
            'web' => (int) env('OFFER_PHOTOS_WEB_SIZE', 1920),
            // La fiche d'une offre : le carrousel sur toute la largeur (400 points, davantage sur tablette).
            'detail' => (int) env('OFFER_PHOTOS_DETAIL_SIZE', 1200),
            // Les listes (recherche, mes logements, favoris, demandes) : la carte sur toute la largeur (354 points).
            'list' => (int) env('OFFER_PHOTOS_LIST_SIZE', 1080),
            // Les carrousels de l'accueil : deux cartes par ligne (173 points).
            'home' => (int) env('OFFER_PHOTOS_HOME_SIZE', 540),
            // Le formulaire d'une offre : les vignettes du champ photos (104 points).
            'form' => (int) env('OFFER_PHOTOS_FORM_SIZE', 320),
        ],
    ],
    // Au-delà, la recherche annonce « Plus de 1000 logements » (décision 2026-11-25-recherche-plus-de-1000).
    'browse_count_cap' => 1000,
];
