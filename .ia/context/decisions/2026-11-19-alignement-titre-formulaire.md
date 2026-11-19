# `2026-11-19` — Alignement du titre du formulaire et des étapes, réglé côté Laravel

- **Type** : décision
- **Question posée** : demande directe de l'humain.
- **Réponse** : « le title de la step ou du formulaire aligné au centre en le faisant depuis la config du formulaire laravel ».
- **Ce qui est retenu** :
  - chaque formulaire déclare l'alignement de ses titres par `Form::titleAlign()`. La valeur est `start` (à gauche) par défaut, ou `center` ;
  - l'API le sert dans la définition du formulaire, en `title_align` ;
  - l'app l'applique au titre et au sous-titre du formulaire, ainsi qu'au titre et au sous-titre de chaque étape. Le sous-titre suit toujours le titre ;
  - le formulaire de l'offre (`offer.offer`) est centré. Les autres formulaires restent à gauche.
- **Motif** : la demande de l'humain ; la mise en page reste pilotée par le serveur, comme le reste du formulaire.
- **Portée** : `Form::titleAlign`, `FormResource`, `OfferForm` ; `DynamicForm` (mobile).
- **Corollaires** : l'indication « Étape n sur N » n'est pas concernée par ce réglage. Une valeur inconnue est lue comme `start` par l'app.
- **Tranché par** : humain
