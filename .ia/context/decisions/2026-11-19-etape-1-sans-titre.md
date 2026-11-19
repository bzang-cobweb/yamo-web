Remplacée par : 2026-11-19-etape-1-presentation.md

# `2026-11-19` — Étape 1 du formulaire de logement sans titre

- **Type** : décision
- **Question posée** : demande directe : « pas de titre à l'étape 1 » ; puis, à ma question : « the step heading, step title can be null in laravel ».
- **Réponse** : retirer le titre d'étape « Le logement », pas le champ « Titre » de l'offre.
- **Ce qui est retenu** :
  - l'étape 1 du formulaire de l'app n'a plus de titre (`title` à `null` dans `RentalDomain::formSteps()`) ; « Étape 1 sur 4 » et les champs restent ;
  - les étapes suivantes gardent leur titre ;
  - le texte `offer::rental.steps.home` est retiré.
- **Motif** : l'en-tête de l'écran dit déjà « Ajouter : Logement ».
- **Portée** : `RentalDomain`, `Domain::formSteps()` (le titre d'une étape peut être `null`).
- **Corollaires** : aucun.
- **Tranché par** : humain
