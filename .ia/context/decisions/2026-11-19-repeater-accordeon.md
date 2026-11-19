Complétée par : 2026-11-19-repeater-icone-supprimer.md (supprimer par une icône sur le titre)

# `2026-11-19` — Champ repeater : chaque groupe est une section repliable, fermée par défaut

- **Type** : décision
- **Question posée** : demande directe de l'humain : « le champ repeater doit fonctionner comme un accordion avec le titre et toujours fermé par défaut ».
- **Réponse** : la demande elle-même ; le détail est choisi par la session.
- **Ce qui est retenu** :
  - dans l'app, chaque groupe d'un champ `repeater` est une section repliable (le composant `Accordion` de la fiche), titrée par la valeur de son premier sous-champ texte, ou « Sans titre » ;
  - les groupes sont fermés par défaut ; les sous-champs et « Retirer ce bloc » sont dans la section ;
  - **choix de la session** : un groupe qu'on vient d'ajouter s'ouvre pour être rempli, et un groupe qui a une erreur de l'API s'ouvre (sinon l'erreur serait cachée).
- **Motif** : demande de l'humain.
- **Portée** : `RepeaterField`, `Accordion` (mode contrôlé : `open`, `onToggle`) ; texte `form.repeaterUntitled` (module Form).
- **Corollaires** : générique, vaut pour tout champ `repeater`.
- **Tranché par** : humain (détail par la session)
