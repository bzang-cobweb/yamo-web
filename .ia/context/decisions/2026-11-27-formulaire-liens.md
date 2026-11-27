# `2026-11-27` — Des liens sous un formulaire servi

- **Type** : décision
- **Question posée** : « rajouter un lien qui renvoie le code si pas verified » (e-mail, téléphone) : il faut un lien
  sous un formulaire, servi par le serveur.
- **Ce qui est retenu** :
  - concept générique du paquet `bza/laravel-form` : `Form::links()` rend une liste de `FormLink::make($label, $form)`,
    servie par `FormResource` sous `links: [{label, form}]` (vide par défaut) ;
  - l'app (`DynamicForm`) affiche chaque lien sous les champs et ouvre `/profile/[form]` ; type `FormDefinition.links`.
- **Décidé seul (mode nuit)** : un lien n'ouvre qu'un autre formulaire (pas une liste ni une adresse) ; il est caché avec
  le formulaire pendant l'étape du code.
- **Portée** : paquet `packages/form` (`Form`, `FormLink`, `FormResource`, README), app (`DynamicForm`, types).
- **Tranché par** : session de nuit
