# Détail d'une réservation : le type de logement à côté du type de location

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active

## Décision
En haut de la page d'une demande, à côté du type de location, s'affiche le type de logement :
« Location · Appartement », « Location meublée · Studio ». `BookingResource` sert
`offer.property_type`, rédigé, ou `null`. L'app le joint à `rental_type` avec « · ». Sans
type de logement, le type de location reste seul.

## Motif
Demande de l'humain du 2026-11-21 : « mettre le type à côté du type de location ».

## Portée
La page d'une demande. Le titre des cartes de la liste des notifications ne change pas : il reste
le type de location seul (décision 2026-11-20-titre-carte).
