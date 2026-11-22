Remplacée par : 2026-11-22-reservations-lien-titre-offre.md

# Mes réservations : le titre en lien vers la fiche du logement

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« dans mes réservations je veux un lien vers le détail de l'offre »)

## Décision

Sur une carte de « Mes réservations », le titre s'affiche en lien (couleur des liens) et ouvre la fiche du logement (`/offers/{id}`). Le reste de la carte ouvre toujours la page de la demande.

## Portée

`BookingListing::item` (`titleLink`). L'app sait déjà afficher un titre en lien (décision 2026-11-21-demandes-lien-membre) : rien ne change côté mobile.
