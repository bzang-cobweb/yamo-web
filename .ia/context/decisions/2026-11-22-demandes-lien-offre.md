# Demandes d'un logement : le titre du logement en lien vers sa fiche

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« faire pareil dans les demandes d'un logement »)
- **Complète** : 2026-11-22-reservations-lien-titre-offre.md, 2026-11-21-demandes-titre-logement-barre.md

## Décision

Dans les demandes d'un logement, le titre du logement, affiché dans la barre du haut, devient un lien (couleur des liens) qui ouvre sa fiche (`/offers/{id}`). Sans logement choisi, le titre « Demandes » n'est pas un lien.

## Portée

- `Listing::titleLinkFor` (nouveau, `null` par défaut), servi en `title_link` par `ListingResource`. `ReceivedBookingListing` le renseigne.
- Mobile : `BackHeader` et `ProfileScreenShell` acceptent `titleLink`, et `ProfileListScreen` le leur passe.
