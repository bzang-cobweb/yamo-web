# `2026-11-18` — Rôle `member` donné à l'inscription depuis l'app

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-18-role-membre.md`.
- **Réponse** : « Approved ».
- **Ce qui est retenu** :
  - `RoleEnum::MEMBER` (`member`) existe, sans permission de base pour l'instant ;
  - le rôle est donné dans `RegisterService::complete()`, et nulle part ailleurs : ni dans le back-office, ni par `CreateUserCommand` ;
  - une migration donne `member` aux comptes existants qui n'ont aucun rôle. Son retour en arrière le retire à ces seuls comptes.
- **Motif** : distinguer les comptes inscrits depuis l'app, et leur donner des droits plus tard (les offres).
- **Portée** : module Auth.
- **Corollaires** : les droits du rôle `member` sur les offres viennent du profil de domaine (décision `2026-11-18-offres-et-domaines`).
- **Tranché par** : humain
