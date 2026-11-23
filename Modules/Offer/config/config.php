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
];
