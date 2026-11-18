# `2026-11-18` — La capacité « champ à suggestions » est retirée de l'app

- **Type** : décision
- **Remplace en partie** : `2026-11-18-adresse-app-simplifiee.md`, dont le corollaire gardait la capacité dans l'app.
- **Question posée** : après le retrait des suggestions de rue de l'app, garder ou non la capacité générique, désormais inutilisée.
- **Réponse** : « supprime la capacité suggestions côté app ».
- **Portée** : app mobile (formulaires génériques) : `SuggestionList`, types et câblage dans `TextField`, `FormField`, `DynamicForm` et `FormService`.
- **Corollaires** :
  - `isApiPath` reste, car il contrôle les envois des formulaires du profil ;
  - côté serveur, `Field::suggestions()` existe encore (sérialisé `suggestions: null`), mais aucun formulaire ne s'en sert et l'app l'ignore ;
  - réintroduire des suggestions dans l'app demandera une version de l'app.
- **Tranché par** : humain
