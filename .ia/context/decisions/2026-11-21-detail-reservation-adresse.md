# Détail d'une réservation : l'adresse avant les dates

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-lignes-demande.md

## Décision
Dans les lignes d'une demande (`BookingResource::lines`, page d'une demande), une ligne
« Adresse » donne l'adresse complète du logement (`Offer::fullAddress()`). Elle se place après
« Personnes » et avant la date de disponibilité et les dates (Arrivée / Visite). Sans adresse, pas
de ligne. Le demandeur et le propriétaire voient la même ligne.

## Motif
Demande de l'humain du 2026-11-21 : « détail réservation mettre l'adresse avant checkin ».
