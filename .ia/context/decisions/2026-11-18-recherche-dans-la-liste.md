# `2026-11-18` — Recherche dans les listes de l'app, côté serveur, validée

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-18-recherche-dans-la-liste.md`.
- **Réponse** : « Approved ».
- **Ce qui est retenu** :
  - **la recherche se fait côté serveur**, via `?search=`, combinable avec `?cursor=` ;
  - la recherche ignore la casse ; `%` et `_` y sont des caractères ordinaires ;
  - un texte vide sert toute la liste ;
  - chaque `Listing` choisit d'être cherchable en déclarant `search()`. La réponse porte `search`, qui vaut le texte d'aide du champ, ou `null` ;
  - « Mes adresses » cherche dans le prénom, le nom, la rue, le NPA et la ville ;
  - dans l'app, `List` a une prop `search` optionnelle, et le champ est affiché hors de la zone qui défile ;
  - l'envoi part 300 ms après la dernière frappe ;
  - aucun résultat : le texte `list.noResults`.
- **Motif** : la liste est paginée. Un filtre dans l'app ne verrait que les pages déjà chargées.
- **Portée** : toutes les listes servies par `GET /api/v1/lists/{name}`.
- **Corollaires** :
  - la recherche du back-office n'est pas touchée ;
  - pas de recherche approximative.
- **Tranché par** : humain
