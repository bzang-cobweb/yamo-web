Remplacée par : 2026-11-21-cartes-titre-compose-en-tete.md (pour l ordre)

# Cartes de l'accueil et de « Mes logements » : titre saisi, ligne du type dessous

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Remplace en partie** : 2026-11-19-item-titre-saisi-details.md (titre et sous-titre des cartes)
- **Complète** : 2026-11-21-reservations-ville-ligne-du-type.md

## Décision
Les cartes de l'accueil et de « Mes logements » prennent le titre et le type des cartes de « Mes
réservations » :
- le titre est le titre saisi de l'offre (« Studio meublé à Bonanjo ») ;
- la ligne en dessous donne le type de location, le type de logement et la ville (« Location
  meublée · Studio · Douala »).

Sans titre saisi, la ligne du type devient le titre. Sans adresse, la ville est omise. Ce qui est
en gras (prix, statut, expiration) ne change pas.

Les trois listes passent par un seul helper, `OfferCardTitle::of()`. La ligne du type vient du
domaine, par `Domain::itemTypeLine()` (anciennement `bookingItemTitle`).

## Motif
Demande de l'humain du 2026-11-21 : « reprendre le titre et le type sur les cards mes logements
et de l'accueil ».

## Portée
Cartes de l'accueil, de « Mes logements » et de « Mes réservations ». Ces cartes ne changent pas :
- les favoris (`OfferFavoritesListing`) ;
- « Réservations reçues » ;
- la liste des logements dans le filtre et le formulaire de réponse : ils gardent le titre
  construit (« Studio · meublé · Douala »).
