# `2026-11-18` — Contrat du chantier rôle admin, liste à défilement infini et composant List, validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-18-liste-infinie-role-admin.md`.
- **Réponse** : « Approved ».
- **Ce qui est retenu** :
  - un rôle `admin` est déclaré dans `RoleEnum`. Le seeder lui **ajoute** `addresses.view|create|update|delete`, sans synchroniser : ce que le back-office lui donne en plus reste ;
  - les listes servies à l'app sont paginées par **curseur**, pas par page :
    - `?cursor=` est optionnel ; la réponse ajoute `next_cursor` ;
    - le serveur fixe la taille (20 par défaut, réglable par liste) ;
    - un curseur invalide sert la première page ;
  - une `Listing` déclare `query()` (ordre stable) et `item(Model)` ; la classe de base pagine ;
  - côté app, le composant `List` générique (`src/shared/components/List.tsx`, sur `FlatList`) ignore l'API. `ProfileListScreen` l'utilise avec `useInfiniteQuery`.
- **Motif** : la liste retire des items en place. Avec une pagination par décalage, un item sauterait ou reviendrait en double ; avec un curseur, non.
- **Portée** : toutes les listes servies par `GET /api/v1/lists/{name}` ; le rôle `admin`.
- **Corollaires** :
  - le registre `2026-11-15-permissions-declarees-dans-le-code` laisse les rôles libres dans le back-office. Le code donne ici au rôle `admin` un socle, qu'il ne fige pas ;
  - l'app ne connaît pas le nombre total d'items.
- **Tranché par** : humain
