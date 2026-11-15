# `2026-11-15` — Contrat du chantier profil, API de connexion et mobile validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-15-profil-api-mobile.md` et ses arbitrages A, B, C.
- **Réponse** : validé avec les recommandations. A : la colonne `name` est supprimée, son contenu repris dans `first_name` / `last_name`, le nom affiché devient « prénom nom », une commande `auth:create-user` remplace `make:filament-user`. B : l'initialisation mobile inclut l'écran de connexion branché sur l'API, l'accueil et la déconnexion. C : dépendances autorisées — `dedoc/scramble` (web, dev), paquets de `create-expo-app`, `expo-secure-store`, `@tanstack/react-query` (mobile).
- **Motif** : une seule source pour l'identité ; prouver de bout en bout que l'API sert le mobile ; OpenAPI dérivé du code.
- **Portée** : ce chantier. Inscription, mot de passe oublié, modification du profil depuis l'app et notifications push restent hors périmètre.
- **Corollaires** : `make:filament-user` ne fonctionne plus (colonne `name` absente) ; le rôle super administrateur s'attribue par `auth:create-user --super-admin`.
- **Tranché par** : humain
