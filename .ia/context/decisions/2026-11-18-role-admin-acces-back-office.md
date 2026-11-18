# `2026-11-18` — Le rôle admin entre au back-office et modifie les utilisateurs

- **Type** : décision
- **Complète** : `2026-11-18-contrat-liste-infinie-role-admin.md`, qui ne donnait au rôle que `addresses.*`.
- **Question posée** :
  - donner au rôle `admin` l'accès au back-office et la consultation des utilisateurs ?
  - puis, une fois constaté que les adresses d'un utilisateur ne s'affichent que sur sa page d'édition, gardée par `users.update`, quelle voie prendre ?
- **Réponse** :
  - « Yes, add them » ;
  - puis « Also give users.update ».
- **Ce qui est retenu** : les permissions de base du rôle `admin` sont `admin.access`, `users.view`, `users.update` et `addresses.view|create|update|delete`. Le seeder les ajoute sans rien retirer.
- **Motif** : atteindre les adresses d'un utilisateur dans le back-office, sans ajouter de page de consultation.
- **Portée** : rôle `admin`.
- **Corollaires** :
  - un admin peut aussi modifier les champs du compte d'un utilisateur (nom, e-mail, rôles proposés hors super administrateur) ;
  - il ne crée ni ne supprime de compte.
- **Tranché par** : humain
