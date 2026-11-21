# « Demandes » : le filtre « Logement » caché

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« cacher le filtre logement dans [ce] cas »)

## Décision

Dans la liste « Demandes », ouverte par « Demandes » d'une carte de « Mes logements », le filtre
« Logement » ne s'affiche plus dans l'écran des filtres. Le logement choisi par la carte filtre
toujours la liste ; il ne compte pas dans le badge des filtres et reste après « Réinitialiser ».
« Mes réservations » garde son filtre « Logement » visible, avec recherche.

## Mécanique

Le serveur sert ce filtre en champ caché (`Field::hidden('offer')`). Dans l'app, tout filtre de type
`hidden` est un filtre fixe : il n'est pas rendu, pas compté, pas effacé par « Réinitialiser ».

## Portée

`ReceivedBookingListing` ; app : `utils/filters.ts`, `FilterScreen`.

## Corollaires

Remplace, pour « Demandes reçues », la recherche du filtre « Logement » de 2026-11-21-filtre-logement-recherche.
