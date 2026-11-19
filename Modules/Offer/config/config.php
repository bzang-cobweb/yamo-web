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
];
