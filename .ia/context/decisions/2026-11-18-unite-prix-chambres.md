Remplacée en partie par : 2026-11-19-ombre-coeur-onglets-offre.md (plus de nombre de chambres dans les listes)
# `2026-11-18` — Logement : unité du prix dans le formulaire ; nombre de chambres avant le prix

- **Type** : décision
- **Question posée** : aucune. Consigne de l'humain :
  > 1. afficher l'unité du prix dans le formulaire selon meublé
  > 2. si c'est plus d'une chambre alors afficher le nombre de chambres dans ListItem avant le prix
- **Ce qui est retenu** :
  - **unité qui suit un autre champ** : un `Field` peut avoir une unité qui dépend de la valeur d'un autre champ (`suffixWhen`, servi en `suffix_when`), son `suffix` restant l'unité par défaut. L'app la met à jour pendant la saisie ;
  - **prix d'un logement** : le libellé est « Loyer » et l'unité affichée est « XAF / jour » si « Meublé » est coché, « XAF / mois » sinon (`Domain::priceUnitWhen()`) ;
  - **sous-titre des listes** : un domaine peut placer des détails avant le prix (`Domain::itemDetails()`). Pour la location, s'il y a plus d'une chambre, leur nombre s'affiche : « 3 chambres · 80 000 FCFA / mois » dans la vitrine, et « Publiée · 3 chambres · … » dans « Mes logements ».
- **Portée** : modules Form (`Field`) et Offer (domaines, formulaire, listes) ; app mobile (formulaire).
- **Tranché par** : humain
