# Mes logements : « Terminée » dans le filtre par statut de demande

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« statut terminée aussi »)
- **Complète** : 2026-11-22-logements-filtre-statut-demande.md

## Décision

Le filtre « Demande » de « Mes logements » propose aussi « Terminée » : En attente, Acceptée, Refusée, Annulée, Expirée, Terminée.
- « Terminée » : la demande est enregistrée terminée, ou acceptée et passée (pas encore passée par `booking:complete`).
- « Acceptée » exclut désormais les demandes acceptées et passées, qui sont terminées.

## Portée

`OfferListing` (`BOOKING_STATUSES`, `applyFilters`).
