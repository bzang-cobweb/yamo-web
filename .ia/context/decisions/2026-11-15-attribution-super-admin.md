# `2026-11-15` — Seul un super administrateur attribue le rôle super administrateur

- **Type** : décision
- **Question posée** : un compte qui a le droit de modifier les utilisateurs peut-il leur donner le rôle `super_admin` ?
- **Réponse** : non. Le champ « Rôles » du formulaire utilisateur ne propose `super_admin` qu'à un super administrateur.
- **Motif** : sinon, la permission « Modifier les utilisateurs » permettrait de s'octroyer tous les droits (élévation de privilèges).
- **Portée** : formulaire utilisateur du back-office. Un rôle créé dans l'admin qui contiendrait toutes les permissions reste attribuable : c'est la permission `roles.update` qui le contrôle.
- **Corollaires** : le premier super administrateur s'attribue en ligne de commande (voir `.ia/context/project.md`, section Environnement).
- **Tranché par** : orchestrateur (à valider par l'humain)
