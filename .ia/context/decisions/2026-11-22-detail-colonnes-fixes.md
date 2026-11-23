# Page d'une demande : des colonnes de largeur fixe

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« fixer la largeur de chaque colonne, la colonne de droite plus grande »)

## Décision

Dans les lignes d'une demande, le libellé occupe toujours 35 % de la largeur. La valeur, alignée à droite, prend tout le reste, soit environ 65 % moins l'écart entre les colonnes. Les colonnes ne dépendent plus de la longueur des textes : une valeur longue passe à la ligne dans sa colonne.

## Portée

Mobile : `NotificationDetailScreen` (styles `lineLabel` et `dateValue`).
