# `2026-11-24` — Plus de statut « Archivé » pour un partenaire

- **Type** : décision
- **Question posée** : « enlever le statut archivé ».
- **Ce qui est retenu** :
  - `PartnerStatusEnum::ARCHIVED` est retiré, avec sa classe de notification `PartnerArchived` et ses textes (fr, en, de).
  - Restent : en validation, en attente de paiement, publié, refusé.
  - Aucun partenaire ni aucune notification n'avaient ce statut en local : pas de migration de données.
  - La migration déjà jouée `2026_12_05_100000_split_partner_notifications_by_status` garde sa table de correspondance : une migration jouée ne se modifie pas, et elle n'a rien d'archivé à traiter.
- **Corollaire** : si un environnement avait un partenaire archivé, il faudrait le passer à un autre statut avant le déploiement. Rien n'a été poussé.
- **Tranché par** : humain
