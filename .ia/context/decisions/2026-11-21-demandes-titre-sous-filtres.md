# « Demandes » : le titre du logement sous les filtres ; cartes sans le nom du membre

Remplace : 2026-11-21-demandes-titre-logement.md

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« enlever le nom du membre sur les cards demandes / mettre le titre du logement
  après les filtres »)

## Décision

- La barre du haut de la liste « Demandes » reste « Demandes ».
- Filtrée sur un logement, le titre de sa carte s'affiche en gras sous la ligne des filtres, en tête
  des cartes ; sans logement choisi, rien.
- Les cartes n'affichent plus le nom du membre : ligne du type, puis type de demande et dates, comme
  dans « Mes réservations ».

## Mécanique

`Listing::headingFor($filters)` (null par défaut), servi en `heading` par l'API des listes ; l'app
l'affiche en tête des items (`List` → `header`). La clé `requests_title` est retirée.

## Portée

`Listing`, `ListingResource`, `ReceivedBookingListing` ; app : `ProfileListScreen`, type `ListDefinition`.
