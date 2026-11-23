# `2026-11-24` — Champ de fichiers : un libellé d'ajout propre au champ

- **Type** : décision
- **Question posée** : le champ Logo du formulaire d'un partenaire affiche « Ajouter des photos » alors qu'il n'accepte qu'une image ; « oui, corrige le libellé du champ logo ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `Field::addLabel()` vaut aussi pour un champ de fichiers ; le libellé est servi dans `upload.add_label` (`null` : le libellé de l'app, « Ajouter des photos ») ;
  - le champ Logo d'un partenaire : « Ajouter un logo » (en « Add a logo », de « Logo hinzufügen ») ;
  - l'app affiche ce libellé sur le bouton d'ajout, et ne montre la pastille « Principale » que si le champ accepte plusieurs fichiers.
- **Motif** : un libellé au pluriel et une « photo principale » n'ont pas de sens pour un fichier unique.
- **Portée** : `Field` (module Form), `PartnerForm`, traductions du module Partner ; `UploadField` et `UploadSettings` de l'app.
- **Corollaires** : les photos d'une offre gardent le libellé de l'app.
- **Tranché par** : humain
