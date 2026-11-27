# `2026-11-27` — « Mes réservations » : un œil vers l'offre, les étoiles vers ses avis, plus de lien sur le titre

Remplace en partie : 2026-11-22-reservations-lien-offre.md (le titre de l'offre en lien vers sa fiche)

- **Type** : décision
- **Question posée** : « mes réservations mettre une icône œil pour voir à droite des stars », puis « œil pour voir l'offre, stars pour voir la liste des avis dans un panneau et enlever le lien sur le titre de l'offre ».
- **Ce qui est retenu** :
  - Sur une carte de « Mes réservations », à droite des étoiles, sur leur ligne : un œil (« Voir », pour l'accessibilité) qui ouvre la fiche de l'offre (`view_link`, `ListItem::viewLink()`).
  - Toucher les étoiles ouvre, dans un panneau à taille maximale titré « Avis », tous les avis de l'offre (`rating_panel`, `ListItem::ratingPanel()`, le panneau `offer.reviews` du registre `ActionPanel`, décision 2026-11-27-action-panneau) : la liste `offer.reviews`, avec sa recherche, sans cadre de page (`ProfileListScreen` `bare`, `OfferReviewsList`, commun avec la page `/offers/[id]/reviews`).
  - Le titre de l'offre n'est plus un lien ; le reste de la carte ouvre toujours la page de la demande.
- **Portée** : web : `packages/form` (`ListItem::viewLink`, `ratingPanel`), `BookingListing` ; mobile : `ListItemCard`, `ItemReviews` (ligne des étoiles, `trailing`, `onRatingPress`), `ProfileListScreen` (`bare`), `OfferReviewsList`, `ActionPanel`, types `ListItem`.
- **Tranché par** : humain
