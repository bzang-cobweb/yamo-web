# `2026-11-19` — Champ repeater : supprimer un groupe par une icône sur son titre

- **Type** : décision
- **Question posée** : demande directe de l'humain : « mettre une icône supprimer sur le titre et enlever le lien à l'intérieur ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - une icône corbeille rouge, dans la barre du titre du groupe, avant la flèche ; visible groupe ouvert ou fermé ; elle retire le groupe sans confirmation (rien n'est enregistré avant « Enregistrer ») ;
  - le lien « Retirer ce bloc » à l'intérieur du groupe disparaît ; son texte reste lu par l'accessibilité sur l'icône ;
  - `Accordion` accepte une action dans sa barre de titre (`action`).
- **Motif** : demande de l'humain.
- **Portée** : `RepeaterField`, `Accordion` (mobile).
- **Corollaires** : complète 2026-11-19-repeater-accordeon.
- **Tranché par** : humain
