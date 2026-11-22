# Mes réservations : le titre saisi de l'offre en lien vers sa fiche

Remplace : 2026-11-22-reservations-lien-offre.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« Mettre plutôt le lien sur le titre de l'offre »)

## Décision

Sur une carte de « Mes réservations », c'est le titre saisi de l'offre (« Studio meublé à Akwa ») qui s'affiche en lien et ouvre sa fiche (`/offers/{id}`). En général, il est en sous-titre, sous la ligne du type (« Location meublée · Studio · Douala »). Si l'offre n'a pas de ligne du type, c'est le titre de la carte. La ligne du type n'est pas un lien. Le reste de la carte ouvre toujours la page de la demande.

## Portée

- `ListItem::subtitleLink` (nouveau, servi en `subtitle_link`) et `BookingListing::item`.
- Mobile : `ProfileListScreen` affiche un sous-titre en lien comme un titre en lien.
