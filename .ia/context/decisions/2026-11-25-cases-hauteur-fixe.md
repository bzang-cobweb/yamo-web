# `2026-11-25` — Une liste de cases à hauteur fixe

- **Type** : décision
- **Question posée** : « champ équipements est trop haut. il faut fixer la hauteur de ce type de champ », puis « fixer aussi l'affichage sur filament ».
- **Ce qui est retenu** : un champ de cases à cocher (`checkbox_group`) a au plus 260 points de haut, dans un cadre bordé. Au-delà, ses cases défilent dans le cadre. Cela vaut pour tous les champs de ce type ; une courte liste (les trois règles) garde sa hauteur naturelle.
- **Au back-office**, pareil : une liste de cases (les « Règles » et « Équipements » d'une offre, et tout champ de cases d'un formulaire décrit) a au plus 16rem de haut, dans un cadre, et défile au-delà (`FieldComponent::boundedCheckboxList`). Ses cases y sont en grille par lignes : en colonnes CSS (le défaut de Filament), une liste plus haute que le cadre débordait sur le côté et passait sur la bordure (« le label est sur la bordure du champ filament »).
- **Tranché par** : humain ; session (260 points, une hauteur au plus plutôt qu'une hauteur imposée, le cadre)
