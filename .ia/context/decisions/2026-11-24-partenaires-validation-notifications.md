# `2026-11-24` — Partenaire ajouté ou modifié : notifications de mise en validation

Remplace en partie : 2026-11-24-partenaires-notifications.md

- **Type** : décision
- **Question posée** (mode nuit) : « la modification ou ajout d'un partenaire le met en mode en validation (notifications envoyées à tous les autres admin(edition) et à l'utilisateur qui a effectué l'opération) ».
- **Réponse** : la demande, lue ainsi : les « administrateurs (édition) » sont les comptes rattachés au partenaire, tous peuvent le modifier (`PartnerPolicy::update`).
- **Ce qui est retenu** :
  - à chaque ajout ou modification depuis l'app (même s'il était déjà en validation), `PartnerService` émet `PartnerSubmitted` ; `NotifyPartnerUsersOfSubmission` appelle `PartnerNotifierService::submitted()`, qui prévient **tous** les comptes rattachés, l'auteur compris, par `PartnerSubmittedNotification` (onglet « Notifications », e-mail et SMS selon les canaux) ;
  - l'auteur lit « Partenaire ajouté » / « Partenaire modifié » et « Votre partenaire « … » est en validation : l'équipe le vérifie… » ; les autres, « « … » a été modifié par Prénom Nom ; il est en validation » ;
  - `statusChanged()` ne part plus pour le passage en validation : sinon deux notifications pour la même action ;
  - la notification d'un partenaire publié dit « accepté » (« Partenaire accepté », « … a été accepté par l'équipe »), en trois langues.
- **Motif** : la demande ; une notification par action.
- **Portée** : module Partner (événement, listener, service, notification, `PartnerService::change()` qui reçoit l'auteur, `MyPartnerController`), traductions ; tests.
- **Corollaires** : un passage en validation fait au back-office (rare) ne prévient plus personne.
- **Tranché par** : humain (demande) ; session de nuit (lecture des « administrateurs », texte « accepté », suppression de la notification de statut en double)
