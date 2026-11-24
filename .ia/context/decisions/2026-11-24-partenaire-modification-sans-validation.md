# `2026-11-24` — Modifier un partenaire ne change plus son statut

Remplace : 2026-11-24-partenaires-par-les-membres.md (seulement « toute modification depuis l'app le repasse en attente »)

- **Type** : décision
- **Question posée** : « annuler le statut modifié » ; précisé : la modification ne change plus le statut.
- **Ce qui est retenu** :
  - `PartnerService::change()` ne repasse plus le partenaire « En validation ». Il garde son statut : affiché, il reste en ligne avec ses nouvelles données.
  - L'ajout depuis l'app reste « En validation ».
  - **La notification d'une modification** :
    - elle a la classe du statut actuel du partenaire, et non plus « en validation » ;
    - ses textes ne parlent plus de validation : « Votre partenaire « … » a été modifié. », « « … » a été modifié par … » (fr, en, de) ;
    - elle part comme avant au compte du back-office, et aux administrateurs par e-mail et SMS.
  - **L'auteur à prévenir d'une action au back-office** est celui de la dernière action depuis l'app, ajout ou modification : la dernière notification du fil qui a un `user_id`.
- **Corollaire** : un partenaire refusé ou archivé que l'on modifie le reste. L'équipe change son statut au back-office.
- **Tranché par** : humain
