# Cartes : les espacements de l'accueil partout

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (« les espacements doivent être la même chose dans tous les cards comme sur les cards
  de l'accueil »)

## Décision

Toutes les cartes de liste ont les espacements de celles de l'accueil : 8 de marge intérieure de chaque côté
(au lieu de 12 sur les côtés), 4 entre la photo, le titre, le sous-titre, le prix, la note, les compteurs (au
lieu de 2, plus 4 en haut et en bas du texte). Le menu ⋮ reste à 8 du coin de la photo.

## Mécanique

`CARD_PADDING` et `CARD_GAP`, exportés par `List`, servent à l'accueil (`BrowseScreen`) et aux listes du
profil (`ProfileListScreen`).

## Non couvert

La taille du titre diffère encore : gras et petit à l'accueil, normal dans les listes du profil.
