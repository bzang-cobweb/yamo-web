# `2026-11-24` — Back-office : « Valider » un partenaire en validation

- **Type** : décision
- **Question posée** (mode nuit) : « l'utilisateur back qui a les droits valide et met le partenaire en statut accepté (notifications envoyées à tous les admins) ».
- **Réponse** : la demande, lue ainsi : le statut accepté du projet est « Publié » ; « tous les admins » sont les comptes rattachés au partenaire (ceux qui peuvent le modifier).
- **Ce qui est retenu** :
  - une action « Valider » (`PartnerResource::validateAction()`), sur la liste et sur la page d'un partenaire, visible seulement s'il est en validation et pour qui peut le modifier (`update` de la policy, droit `partners.update`) ; avec confirmation ;
  - elle appelle `PartnerService::accept()`, qui le passe en « Publié » ; le changement de statut prévient ses comptes (« Partenaire accepté », décision 2026-11-24-partenaires-validation-notifications), sauf l'auteur s'il en est un ;
  - le libellé du statut reste « Publié » au back-office et « Affiché » / « Pas affiché » dans « Mes partenaires ».
- **Motif** : la demande ; un geste au lieu de changer le statut dans le formulaire.
- **Portée** : `PartnerResource`, `PartnerTable`, `EditPartner`, `PartnerService`, traductions ; tests.
- **Corollaires** : refuser reste un changement de statut dans le formulaire (non demandé).
- **Tranché par** : humain (demande) ; session de nuit (« accepté » = « Publié », destinataires, action « Valider » sur la liste et la page)
