# Fiche d'un logement : seule la première section ouverte

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« ouvrir seulement le 1er accordion par défaut »)
- **Complète** : 2026-11-22-fiche-sections-ordre.md

## Décision

À l'arrivée sur la fiche, seule la première section est ouverte (les caractéristiques). Description, Localisation, Règles, Avis et blocs d'informations sont fermés. Si le logement n'a pas de caractéristiques, la première section servie s'ouvre (règle existante).

## Portée

`config('offer.sections')` : `facts` ouverte, les autres fermées.
