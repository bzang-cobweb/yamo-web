# Mes réservations : le cœur des favoris à la place du menu trois points

Remplace : 2026-11-21-reservations-menu.md, 2026-11-22-reservations-menu-annuler.md

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« mes réservations remplacer le dropdown 3 petits points par le cœur des favoris »)

## Décision

Sur une carte de « Mes réservations », le menu trois points disparaît. À sa place, sur la photo, en haut à droite, il y a le cœur des favoris du logement, allumé s'il est en favori. Le toucher ajoute le logement aux favoris ou l'en retire, comme à l'accueil, et la carte reste dans la liste. Le statut reste sur la photo, en haut à gauche.

« Modifier » et « Annuler » ne sont plus sur la carte : on les trouve sur la page de la demande, en bas.

## Portée

`BookingListing` : plus de `actionsMenu`, plus d'actions ; `favorite` sur chaque carte, avec `withFavoriteOf` sur l'offre. Rien ne change côté mobile : le cœur est déjà générique (`ItemPicture`, `FavoriteHeart`).
