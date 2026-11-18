<?php

return [
    'name' => 'Offer',

    /*
     * Domaine du projet (décision 2026-11-18-offres-et-domaines) : clé d'un domaine
     * inscrit dans `DomainRegistry` (`rental`…). Vide : le module n'expose rien.
     * Une clé inconnue est une erreur au démarrage.
     */
    'domain' => env('OFFER_DOMAIN') ?: null,
];
