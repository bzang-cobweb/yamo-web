Remplacée en partie par : 2026-11-24-partenaires-validation-notifications.md

# `2026-11-24` — Partenaires : une notification à chaque changement de statut

- **Type** : décision
- **Question posée** : « et des notifications par changement de statut aux autres utilisateurs même non admin ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - tout changement de statut d'un partenaire (back-office, app ou commande) émet `PartnerStatusChanged` ; le listener `NotifyPartnerUsersOfStatus` appelle `PartnerNotifierService::statusChanged()` ;
  - sont prévenus tous les comptes rattachés, administrateurs ou non, sauf celui qui a fait le changement (un membre qui modifie son partenaire, qui repasse en attente) ;
  - la notification `PartnerStatusNotification` : dans l'onglet « Notifications » (« Partenaire publié », « Votre partenaire « … » est publié. »), et par e-mail et SMS selon les canaux du compte ; elle ouvre « Mes partenaires » ; textes dans la langue du destinataire ;
  - pas de notification quand le statut ne change pas.
- **Motif** : la demande de l'humain.
- **Portée** : module `Partner` (évènement, listener, service, notification, modèle).
- **Corollaires** : la notification est une carte à elle (sans fil) et n'a pas de type dans le filtre « Type » des notifications ; aucune notification à l'équipe quand un membre propose un partenaire.
- **Tranché par** : humain
