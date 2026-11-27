# `2026-11-27` — L'intervalle en curseur à deux poignées ; le prix des filtres ainsi

Complète : 2026-11-19-contrat-multiselect-intervalle-equipements.md, 2026-11-27-cle-range.md

- **Type** : décision
- **Question posée** : « faire un autre champ range avec 2 curseur qui permet de renvoyer le min et le max », puis « remplacer dans tous les filtres le filtre min et max par ce nouveau champ ».
- **Ce qui est retenu** :
  - le type `range` (valeur `[min, max]`) prend `->slider($min, $max, $step)` : ses bornes et son incrément partent dans la clé commune `range`, et la validation de chaque valeur les suit (`between`, `multiple_of`) ; sans `slider()`, les deux saisies « Min » et « Max » comme avant ;
  - l'app : avec des bornes servies, la plage choisie en grand (« 50 000 – 200 000 », l'unité dans l'aide) et un curseur à deux poignées (un toucher place la plus proche, un glissement horizontal la déplace, l'une ne dépasse pas l'autre ; l'accessibilité règle chacune, « Min » et « Max ») ; une poignée laissée au bout ne borne rien (valeur vide) ;
  - le curseur est un seul composant (`Slider`), à une poignée pour la note, à deux pour l'intervalle ; les bornes numériques sont une seule notion côté serveur (`bounds()`, pour `ratingRange()` et `slider()`) ;
  - le seul filtre « Min / Max », le prix (recherche, Mes logements, favoris), passe en curseur : de 0 au prix le plus haut des offres de la liste, arrondi à un pas rond (1, 2 ou 5 fois une puissance de 10, une cinquantaine de crans) ; sans offre avec un prix, les deux saisies.
- **Tranché par** : humain
