# `2026-11-26` — Mes réservations : statut, période, région, type et type de location en puces

Complète : 2026-11-21-reservations-filtre-statut.md, 2026-11-21-reservations-periode-dates.md, 2026-11-21-reservations-filtre-type.md, 2026-11-26-filtres-en-puces.md

- **Type** : décision
- **Question posée** : « mes réservations filtres, afficher les filtres statut, période, région, type et type de location en chips ».
- **Ce qui est retenu** : dans les filtres de « Mes réservations », ces cinq filtres sont des puces, plusieurs valeurs à la fois ; une demande est gardée si elle correspond à l'une des valeurs choisies de chaque filtre (« En attente » reste sans les expirées). Ville et logement restent en liste déroulante. « Demandes » d'une offre garde ses listes déroulantes.
- **Corollaires** : l'API attend alors un tableau (`filter[status][]=pending`). Type et type de location viennent du domaine (`offerTypeFilters`), qui ne sert qu'à « Mes réservations ». `OfferRegions::apply` accepte une ou plusieurs régions.
- **Tranché par** : humain
