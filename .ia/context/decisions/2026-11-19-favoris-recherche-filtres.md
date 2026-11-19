# Mes favoris : recherche et filtres de l'accueil

- **Type** : décision de conception
- **Date** : 2026-11-19
- **Décidé par** : l'humain (« faire la même liste pour mes favoris avec la recherche et les filtres de l'accueil »), complété par l'agent pour le bouton du bas

## Décision

La liste « Mes favoris » (`OfferFavoritesListing`) reprend :

- la recherche de l'accueil (titre dans toutes les langues, ville) ;
- les filtres de l'accueil, via le trait `FiltersOffers`. Leurs options sont calculées sur les seules offres en favori : seules les villes de ses favoris sont proposées.

Elle reçoit aussi le bouton fixe « Rechercher un logement », qui mène à l'accueil, comme Mes réservations. **Ce bouton est un choix de l'agent**, pour la cohérence avec Mes réservations.

## Motif

Une même liste d'offres se cherche et se filtre partout de la même façon. Les filtres vivent dans un seul trait, partagé par l'accueil, Mes logements et Mes favoris.

## Portée

Serveur uniquement : l'app affiche la recherche, les filtres et le bouton du bas, que décrit l'API des listes génériques.

## Corollaires

- Un filtre ajouté à `FiltersOffers` apparaît aussi dans Mes favoris.
- Retirer le bouton du bas : retirer `footer()` de `OfferFavoritesListing`.
