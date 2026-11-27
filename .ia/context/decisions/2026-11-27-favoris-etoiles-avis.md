# `2026-11-27` — « Mes favoris » : les étoiles ouvrent les avis

- **Type** : décision
- **Question posée** : « mes favoris cards mettre les stars et le lien comme sur la page mes réservations », puis « j'ai dit mettre les avis… pas l'œil ».
- **Ce qui est retenu** : sur une carte de « Mes favoris », toucher les étoiles ouvre les avis de l'offre dans un panneau titré « Avis » (`ratingPanel`, panneau `offer.reviews`), comme sur « Mes réservations » (décision 2026-11-27-reservations-oeil-voir). Pas d'œil : la carte ouvre déjà la fiche de l'offre. Le titre n'est pas un lien. Un logement sans avis n'a pas d'étoiles, donc rien à toucher.
- **Portée** : web, `OfferFavoritesListing::item()`. Aucun changement mobile.
- **Tranché par** : humain
