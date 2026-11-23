# Fiche d'un logement : caractéristiques, description, localisation

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« enlever type de logement et type de location dans caractéristiques / déplacer la description dans un accordion après les caractéristiques / déplacer l'accordion Localisation après les caractéristiques »)
- **Complète** : 2026-11-22-fiche-titres-centres.md, 2026-11-22-description-voir-plus.md

## Décision

- Les caractéristiques n'ont plus le type de logement ni le type de location : ils sont dans le titre composé, sous le titre. La catégorie reste en tête.
- La description n'est plus un texte seul sous le titre. Elle a sa section repliable « Description » (en : Description, de : Beschreibung), avec son « Voir plus » au-delà de 4 lignes.
- L'ordre des sections : Caractéristiques, Description, Localisation, puis Règles, Avis et les blocs d'informations. Les trois premières sont ouvertes à l'arrivée, les autres fermées.

Cela remplace en partie 2026-11-19-fiche-description-equipements (la description sans titre) et l'ordre de 2026-11-19-fiche-offre-ordre.

## Portée

- `config('offer.sections')` (nouvelle clé `description`) et `OfferDetailResource::sections`.
- `RentalDomain::adjustFacts`, qui retire les deux types, et `leadingFacts`, qui ne garde que la catégorie.
- Mobile : la section `description` rend le texte replié (`TextSection` `collapsible`).
