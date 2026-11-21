# Filtre « Libre dès le » : pas pour la location meublée

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« le filtre libre dès le visible uniquement si type de location n'est pas location
  meublé »)

## Décision

Dans les filtres de l'accueil, « Libre dès le » s'affiche tant que le type de location n'est pas « Location
meublée » : sans type choisi, « Location » ou « Colocation ». Choisir « Location meublée » le masque, et sa
valeur n'est plus appliquée.

## Mécanique

`visibleWhen('rental_type', ['', ...unfurnishedValues])` sur le filtre ; l'app masque et n'applique pas un
filtre dont la condition n'est pas remplie (déjà en place).

## Corollaires

Prolonge 2026-11-20-disponibilite-sans-meuble (pas de date de disponibilité pour un meublé).
