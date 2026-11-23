# Fiche d'un logement : le titre et le titre composé, centrés

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« detail offre mettre titre composé après le titre aligné au centre / aligner aussi le titre au centre »)

## Décision

Sous les photos de la fiche d'un logement :
- le titre saisi (« Appartement 3 pièces à Bonapriso »), centré ;
- juste dessous, le titre composé (« Location · Appartement · Douala »), centré, en gris.

Sans titre composé, le titre est seul.

## Portée

`OfferDetailResource` sert `composed_title` (la ligne du type de `OfferCardTitle::of`). Mobile : `OfferDetailScreen`.
