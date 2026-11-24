# `2026-11-24` — Une notification de partenaire par statut

Remplace : 2026-11-24-partenaires-notifications-filtre.md

- **Type** : décision de conception
- **Question posée** : « il faut faire un type de notification par statut comme pour booking ».
- **Ce qui est retenu** :
  - `PartnerNotification` devient une classe abstraite, avec une classe par statut : `PartnerPending` (ajouté ou modifié depuis l'app), `PartnerPublished`, `PartnerRejected`, `PartnerArchived`. On la crée avec `PartnerNotification::for($partner, $status, actor…)`. `PartnerStatusEnum::notification()` donne la classe de chaque statut, `fromNotification()` le statut d'une classe. `data.partner_status` reste enregistré.
  - Le filtre « Type » propose le groupe « Partenaire » (tous les statuts), puis une option par statut présent : « Partenaire en validation », « Partenaire accepté », « Partenaire refusé », « Partenaire archivé ».
  - Le filtre « Statut du partenaire » est gardé, visible pour le groupe « Partenaire » et pour chaque statut. Il filtre sur la classe de la notification, donc sur le statut qu'elle annonce.
  - La pastille et le titre de la carte se lisent depuis la classe, dans la langue du lecteur. En validation, le titre reste « Partenaire ajouté » ou « Partenaire modifié » (`data.created`).
  - La recherche par nom, qui agit, et les textes de l'auteur et des autres comptes ne changent pas.
- **Migration** : `2026_12_05_100000_split_partner_notifications_by_status` donne aux notifications déjà enregistrées la classe de leur statut. Elle se défait.
- **Motif** : le même mécanisme que les demandes de logement (2026-11-22-notification-par-action, 2026-11-22-filtre-type-par-action).
- **Corollaires** :
  - Une notification encore en file sous `PartnerSubmittedNotification` ou `PartnerStatusNotification` ne serait plus traitée : vider la file avant un déploiement.
  - Une ancienne notification d'ajout ou de modification n'a pas `data.created` : sa carte s'intitule « Partenaire en validation ».
- **Tranché par** : humain (une classe par statut, garder le filtre « Statut du partenaire ») ; session (titres des options)
