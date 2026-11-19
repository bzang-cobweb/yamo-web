# `2026-11-19` — Étapes du formulaire de logement : ordre final

- **Remplace** : `2026-11-19-etapes-formulaire-reordonnees.md`.
- **Type** : décision
- **Question posée** : demande directe : « le statut va à l'étape 2 », « l'étape 4 devient l'étape 2 », « l'étape 2 devient l'étape 3 ».
- **Réponse** : la demande, appliquée dans l'ordre. Le statut rejoint d'abord l'étape 2 d'alors (« Prix et disponibilité »), puis les étapes sont renumérotées.
- **Ce qui est retenu** :
  1. « Le logement » : type, meublé, catégorie, titre, description ;
  2. « Caractéristiques » : surface, pièces, chambres, équipements ;
  3. « Prix et disponibilité » : loyer, charges, disponible dès le, statut ;
  4. « Adresse » : rue, code postal, ville.
- **Motif** : la demande de l'humain.
- **Portée** : `RentalDomain::formSteps()`.
- **Corollaires** : si le statut devait aller à la nouvelle étape 2 (« Caractéristiques »), il suffit de déplacer un nom de champ.
- **Tranché par** : humain (lecture de l'ordre : la mienne)
