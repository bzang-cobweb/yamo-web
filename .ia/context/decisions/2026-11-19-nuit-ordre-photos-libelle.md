Remplace en partie : 2026-11-19-contrat-champ-upload-photos.md (la façon de changer l'ordre des photos)

# `2026-11-19` — Ordre des photos par flèches, libellé du champ masqué (mode nuit)

- **Type** : décision
- **Question posée** : demande « offre formulaire : changer l'ordre des photos, enlever le label du champ upload », traitée en mode nuit.
- **Réponse** : aucune (mode nuit).
- **Ce qui est retenu** :
  - chaque photo du champ `upload` se déplace d'une place à gauche ou à droite (‹ ›) ; l'étoile « Mettre en premier » disparaît ; la première reste la principale (badge) ;
  - `Field::hideLabel()` (générique) : le libellé n'est pas affiché par l'app (`label_hidden`), mais reste lu par l'accessibilité et sert aux messages de validation ; le champ `photos` l'utilise, le titre d'étape « Photos » suffisant ;
  - la marque d'obligation (*) disparaît avec le libellé.
- **Motif** : demande de l'humain ; des flèches plutôt qu'un glisser-déposer : aucune dépendance ajoutée la nuit, moins de mécanique.
- **Portée** : module Form (`Field`) ; app (`FieldShell`, `UploadField`) ; `OfferForm`.
- **Corollaires** : un glisser-déposer reste possible plus tard (dépendance à autoriser).
- **Tranché par** : session de nuit
