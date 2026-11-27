# `2026-11-27` — Tous les filtres de statut en puces

Complète : 2026-11-26-reservations-filtres-en-puces.md, 2026-11-22-logements-filtre-statut-demande.md

- **Type** : décision
- **Question posée** : « tous les filtres statut dans les filtres doivent s'afficher en chips », puis « mes logements filtres le filtre demande doit s'afficher en chips ».
- **Ce qui est retenu** : tous les filtres de statut sont des puces, plusieurs à la fois : le statut de l'annonce et le statut des demandes (« Demande ») de Mes logements, le statut et la période de « Demandes » d'une offre, comme Mes réservations et les notifications. Un élément est gardé s'il correspond à l'un des statuts choisis ; chaque liste garde ses règles (« En attente » sans les expirées ; dans Mes logements, « Acceptée » pas encore terminée, « Terminée » et « Expirée » calculées).
- **Corollaire** : le filtre du statut réel d'une liste de demandes a un seul domicile, `BookingFilters::statuses()` et `BookingFilters::applyStatuses()`, partagé par Mes réservations et Demandes. L'API attend des tableaux (`filter[status][]=…`).
- **Tranché par** : humain
