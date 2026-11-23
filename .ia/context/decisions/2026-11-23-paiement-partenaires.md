Remplace en partie : 2026-11-23-module-partenaires.md (la fin de campagne).

# `2026-11-23` — Appliquer le paiement pour les partenaires

- **Type** : décision
- **Question posée** : « ajouter appliquer paiement pour les partenaires boolean dans application (renommer l'autre en appliquer paiement pour les propriétaires) ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - onglet Paiement de la page « Application » : « Appliquer le paiement pour les propriétaires » (l'ancien « Appliquer le paiement », même réglage `payment_required`) et « Appliquer le paiement pour les partenaires » (`partner_payment_required`, décoché par défaut) ;
  - comme pour les offres : coché, un partenaire n'est visible dans l'app que jusqu'à sa date « Expire le », et jamais sans date ; décoché, la date est ignorée ;
  - ni l'un ni l'autre n'est servi à l'app.
- **Motif** : la demande de l'humain ; la même règle que les offres, pour un seul sens de « payé ».
- **Portée** : `Setting`, `SettingResource`, `Partner::scopeVisible()`.
- **Corollaires** : avant ce réglage, une date d'expiration passée masquait toujours un partenaire ; elle ne le masque plus que si le paiement des partenaires est appliqué.
- **Tranché par** : humain
