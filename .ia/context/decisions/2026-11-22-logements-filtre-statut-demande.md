# Mes logements : filtrer par statut de demande

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« dans mes logements, filtres : ajouter un filtre pour les logements qui ont au moins une demande en attente, acceptée, refusée, annulée, expirée »)

## Décision

Le filtre « Demande » de « Mes logements » propose maintenant les statuts eux-mêmes : En attente, Acceptée, Refusée, Annulée, Expirée. Il garde les logements qui ont au moins une demande dans ce statut. Il remplace l'ancien filtre par regroupement (à venir, en attente, passée), sous la même clé `booking_status`.

- « En attente » : en attente et pas expirée.
- « Expirée » : enregistrée expirée, ou en attente et passée (pas encore passée par `booking:expire`).
- « Acceptée » : le statut accepté seul. Une demande terminée (`completed`) n'y est pas.

## Portée

`OfferListing::filters` et `applyFilters` ; `BookingFilters::statusSelect` retiré.
