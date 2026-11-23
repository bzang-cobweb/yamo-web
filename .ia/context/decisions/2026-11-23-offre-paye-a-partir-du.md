# `2026-11-23` — Offre : « Payé à partir du », en face de « Payé jusqu'au »

- **Type** : décision
- **Question posée** : « ajouter aussi le champ pour date de début pour compléter expires_at pour les offres ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - colonne `offers.starts_at`, champ « Payé à partir du » dans l'onglet Accès du back-office, avant « Payé jusqu'au » ;
  - comme `expires_at`, effective seulement si le paiement des propriétaires est appliqué : une offre n'est alors visible des autres qu'entre le début (vide : tout de suite) et la fin ; l'auteur la voit toujours ;
  - « Mes logements » affiche « Payé à partir du <date> » tant que le début est à venir, avant « Payé jusqu'au ».
- **Motif** : la demande de l'humain ; la même règle que la fin, et que la campagne d'un partenaire.
- **Portée** : modèle `Offer` (`scopeVisibleToOthers`, `isVisibleToOthers`), `OfferForm` du back-office, `OfferListing`.
- **Corollaires** : la date ne se saisit qu'au back-office ; l'app ne la modifie pas.
- **Tranché par** : humain
