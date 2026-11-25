Remplacée par : 2026-11-26-croix-etapes-seulement.md

# `2026-11-25` — Formulaires à étapes : la croix de fermeture à partir de la 2e étape

- **Type** : décision
- **Question posée** : « mettre l'icône pour fermer la page sur les formulaires avec step et seulement à partir de la 2ème step ».
- **Ce qui est retenu** :
  - **Dans un formulaire à étapes**, l'en-tête montre la croix à partir de l'étape 2 ; elle ferme la page du formulaire (retour à l'écran d'avant). À l'étape 1, pas de croix, même loin du départ. La flèche, elle, revient toujours à l'étape précédente.
  - **Ailleurs** (sans étapes), rien ne change : la croix apparaît à plus d'un écran du départ et y ramène.
  - **Comment** : le formulaire donne son étape au `BackStackProvider` (`useFormStep`), que lit `BackHeader`.
- **Tranché par** : humain ; session (« fermer la page » = revenir à l'écran d'avant le formulaire)
