Remplace en partie : 2026-11-23-accueil-nombres-reglables.md (« l'ordre ne change plus l'affichage »).

# `2026-11-23` — Partenaires triables par glisser-déposer

- **Type** : décision
- **Question posée** : « rajouter le sortable sur Partner ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - la liste des partenaires du back-office se trie par glisser-déposer (`position`) ; plus de champ « Ordre » dans le formulaire ;
  - un nouveau partenaire se range à la fin ;
  - dans l'app, les partenaires restent tirés au hasard (autant que réglé), puis s'affichent dans l'ordre du back-office.
- **Motif** : la demande de l'humain ; l'ordre sert ainsi à l'affichage sans fixer qui est tiré.
- **Portée** : `PartnerTable`, `PartnerForm`, modèle `Partner`, `PartnerService::shown()`.
- **Corollaires** : aucun changement dans l'app.
- **Tranché par** : humain
