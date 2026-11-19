Remplace en partie : 2026-11-19-contrat-paiement-visibilite-avis.md (le libellé « Date d'expiration » ; la colonne reste `expires_at`).

# `2026-11-19` — « Date d'expiration » devient « Payé jusqu'au »

- **Type** : décision
- **Question posée** : un meilleur nom que « Date d'expiration » ; « Visible jusqu'au » écarté (« ne fait pas allusion au paiement, ce n'est pas une volonté de l'utilisateur ») ; puis « date d'expiration remplacé par payé jusqu'au ? ».
- **Réponse** : « Payé jusqu'au ».
- **Ce qui est retenu** :
  - libellé du champ (back-office, onglet « Accès ») : « Payé jusqu'au » / « Paid until » / « Bezahlt bis » ;
  - « Mes logements » : « Payé jusqu'au 30 juin 2027 » ; date passée : « Paiement échu le 2 janvier 2026 » (en, de traduits) ;
  - l'aide « Appliquer le paiement » parle de la date « Payé jusqu'au ».
- **Motif** : la demande de l'humain ; le nom dit que la date vient du paiement.
- **Portée** : textes `offer::offer` et `settings` ; aucun changement de colonne ni d'API.
- **Corollaires** : « Paiement échu » pour une date passée est un choix de l'agent.
- **Tranché par** : humain ; libellé de la date passée par l'agent
