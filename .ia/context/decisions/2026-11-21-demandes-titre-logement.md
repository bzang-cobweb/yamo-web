Remplacée par : 2026-11-21-demandes-titre-sous-filtres.md

# « Demandes » : le nom du logement dans le titre

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« mettre le nom du logement dans le titre Demandes »)

## Décision

Filtrée sur un logement (ouverte par « Demandes » de sa carte), la liste est titrée
« Demandes · <titre du logement> », le titre de sa carte (le titre saisi, à défaut la ligne du type).
Sans logement choisi : « Demandes ».

## Portée

`ReceivedBookingListing::titleFor()` ; clé `offer::booking.profile.requests_title` rétablie (fr, en, de).

## Corollaires

Remplace, pour le titre, 2026-11-21-demandes-comme-mes-reservations (« titrée Demandes, quel que soit le filtre »).
