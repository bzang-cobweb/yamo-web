# Filtre « Logement » avec une recherche

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« filtres logement avec recherche »)

## Décision

Le filtre « Logement » de « Mes réservations » et celui de « Demandes reçues » ouvrent une liste
avec un champ de recherche (`Field::select(...)->searchable()`), comme le choix du pays.

## Motif

Un membre ou un propriétaire peut avoir beaucoup de logements : les retrouver par leur titre.

## Portée

`BookingListing` et `ReceivedBookingListing` ; l'app gère déjà `searchable` pour une liste déroulante.

## Corollaires

Complète 2026-11-21-reservations-filtre-logement.
