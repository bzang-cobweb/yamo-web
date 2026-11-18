# `2026-11-18` — Logement : prix par nuit si meublé, sinon par mois

- **Type** : décision
- **Question posée** : aucune. Consigne de l'humain :
  > quand c'est meublé c'est le prix par nuitée autrement c'est le prix par mois
  > ajouter cette information sur le sous titre de ListItem séparé par "/"
- **Ce qui est retenu** :
  - un domaine peut donner l'unité du prix d'une offre (`Domain::priceUnit()`), et le prix formaté s'écrit « prix / unité » (`Domain::formattedPrice()`) ;
  - pour la location, l'unité est « nuit » pour un meublé et « mois » sinon (en anglais, « night » et « month ») ;
  - le sous-titre des listes s'écrit « 60 000 FCFA / nuit » dans la vitrine, et « Publiée · 85 000 FCFA / mois » dans « Mes logements » ;
  - la fiche affiche la même unité ;
  - le libellé du prix de la location devient « Loyer » au lieu de « Loyer mensuel », qui serait faux pour un meublé ; idem dans le formulaire.
- **Portée** : module Offer (domaines, listes, fiche).
- **Corollaires** : le formulaire ne dit pas encore l'unité pendant la saisie, puisqu'elle dépend de la case « Meublé ». À ajouter si besoin.
- **Tranché par** : humain
