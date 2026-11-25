# `2026-11-25` — « Plus de 1000 logements » en tête de la recherche

- **Type** : décision
- **Question posée** : « page de recherche : x logements remplacer par plus de 1000 logements si > 1000 ».
- **Ce qui est retenu** :
  - Dans la recherche, au-delà de 1000 offres trouvées, l'intitulé dit « Plus de 1000 logements » (« More than 1000 homes », « Mehr als 1000 Unterkünfte »).
  - Jusqu'à 1000 inclus, il garde le nombre exact.
  - Le plafond se règle dans `config('offer.browse_count_cap')`, à 1000 par défaut.
  - Les autres listes gardent leur nombre exact : « Mes logements », favoris.
- **Tranché par** : humain
