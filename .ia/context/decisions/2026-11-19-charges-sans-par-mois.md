# `2026-11-19` — Fiche : « Charges mensuelles » sans « / mois »

- **Type** : décision
- **Question posée** : « offre détail caractéristiques charges mensuelles enlève / mois ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : sur la fiche, un montant s'affiche avec le seul symbole de la devise (« 15 000 FCFA ») ; le libellé dit déjà la période. Dans le formulaire, l'unité reste « FCFA / mois » à côté de la saisie. Le prix (« 250 000 FCFA / mois ») n'est pas concerné.
- **Motif** : la demande de l'humain.
- **Portée** : `Field::inCurrency(..., withPer: false)` (module Form), `OfferDetailResource`.
- **Corollaires** : aucun.
- **Tranché par** : humain
