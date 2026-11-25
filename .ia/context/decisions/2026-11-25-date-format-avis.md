# `2026-11-25` — « Disponible dès le » au format de date des avis

- **Type** : décision
- **Question posée** : « utiliser le format de date des reviews pour disponible dès le ».
- **Ce qui est retenu** :
  - **Le format** : sur la fiche d'une offre, « Disponible dès le » s'affiche au format court des avis, « 1 déc. 2026 », et non plus « 1 décembre 2026 ».
  - **Où c'est fait** : dans `Field::displayValue()`, qui affiche toute date d'un attribut d'offre (format `ll`). « Disponible dès le » en est aujourd'hui la seule. La date de disponibilité d'une demande avait déjà ce format.
- **Tranché par** : humain ; session (la règle placée dans `Field`, pour toute date d'attribut)
