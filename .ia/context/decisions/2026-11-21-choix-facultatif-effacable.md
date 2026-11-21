# Un choix facultatif se retire

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active

## Décision
Dans les formulaires de l'app, un champ facultatif peut revenir à « non renseigné » :
- **Liste déroulante** : une première ligne « Aucun » (texte `form.none`) retire la valeur. Elle
  envoie une valeur vide, que le serveur enregistre comme nulle. Le correctif est dans
  `OptionsModal`, l'option `clearable`.
- **Boutons radio** : un second toucher sur l'option cochée la décoche.

Un champ obligatoire ne change pas. Une liste qui a déjà une option vide, comme « Tous » dans un
filtre, n'a pas de « Aucun » en plus. Cela vaut pour tous les champs de ce type, dont les règles
Oui / Non du logement : fumeurs, animaux, fêtes.

## Motif
Demande de l'humain du 2026-11-21 : « dans le formulaire de l'offre quand je coche non fumeurs je
ne peux plus ne rien sélectionner. Corriger cela pour tous les champs du même type ».
