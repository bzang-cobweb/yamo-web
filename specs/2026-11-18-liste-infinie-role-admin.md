# Rôle admin, liste à défilement infini, composant List générique

Demande du 2026-11-18, après le chantier `2026-11-18-adresse-du-compte-modele` :

> 1. donne les permissions addresses au rôle admin
> 2. prévoir un scroll infini de la liste. comment le faire ? faire une requête qui charge à chaque fois les n prochains éléments de la liste ?
> 3. faire un composant mobile List générique qui pourra être utilisé par tous les features

## Ce que j'ai compris

### 1. Rôle admin

Il n'existe aujourd'hui qu'un rôle, `super_admin`, qui court-circuite les policies. Il n'y a **pas de rôle `admin`** : ni dans `RoleEnum`, ni en base.

- `RoleEnum` gagne `ADMIN = 'admin'`, avec ses libellés fr/en.
- `AuthDatabaseSeeder` crée le rôle et lui **ajoute** `addresses.view|create|update|delete`.
  - C'est un ajout, pas une synchronisation : une permission donnée à la main au rôle dans le back-office reste en place quand le seeder est rejoué.
  - Le registre dit que « les rôles se gèrent librement dans le back-office ». Ici, le code donne au rôle un socle minimal. Il ne fige pas le rôle.
- Le seeder est rejoué en local (`ddev artisan db:seed`).

### 2. Défilement infini : comment

Oui, une requête charge à chaque fois les *n* éléments suivants. Mais elle repère la suite par un **curseur**, pas par un numéro de page.

| | Page (`?page=3`, décalage) | Curseur (`?cursor=…`) |
|---|---|---|
| Un item retiré pendant le défilement | la suite saute un élément | rien ne saute ni ne se répète |
| Un item ajouté pendant le défilement | un élément revient en double | idem |
| Coût en base sur une longue liste | `OFFSET` relit tout ce qui précède | lecture par index, depuis le dernier vu |
| Nombre total connu | oui | non, et l'app n'en a pas besoin |

La liste retire des items en place. Le curseur est donc le bon choix. Laravel le fournit (`cursorPaginate`).

**Serveur** (`GET /api/v1/lists/{name}`) :

- `?cursor=` est optionnel ; sans lui, la requête sert la première page.
- La réponse garde `name`, `title`, `empty`, `create` et `items`. Elle gagne `next_cursor` : une chaîne, ou `null` quand il n'y a plus rien.
- Le serveur fixe *n* : 20 par défaut, réglable par liste. L'app n'envoie pas de taille.
- Un curseur invalide ou falsifié est traité comme une première page, sans erreur 500.
- Une `Listing` ne rend plus un tableau `items()`. Elle déclare :
  - `query()`, la requête Eloquent avec un ordre stable (par `id` pour les adresses) ;
  - `item(Model): ListItem`, la mise en forme d'un enregistrement.

  La classe de base pagine. Chaque liste future est paginée sans rien écrire de plus.
- La documentation OpenAPI est régénérée.

**App** :

- `useListDefinition` passe à `useInfiniteQuery`. La page suivante est demandée avec le `next_cursor` de la précédente.
- Le titre, le texte vide et le bouton de création viennent de la première page.
- Les items sont mis bout à bout.
- Après un retrait, ou au retour d'un formulaire enregistré, la liste repart de la première page.
- Tirer vers le bas recharge la liste.

### 3. Composant mobile `List` générique

La liste est découpée en deux couches.

- **`List`**, dans `src/shared/components/List.tsx`, est générique et ne connaît ni l'API ni les listes du serveur. Elle repose sur `FlatList` et prend :
  - `items` (`T[]`), `keyExtractor` et `renderItem` ;
  - `onEndReached` et `hasMore` : la page suivante est demandée en approchant du bas, une seule fois à la fois ;
  - `loadingMore` : un indicateur en pied de liste ;
  - `refreshing` et `onRefresh` : tirer pour recharger ;
  - `empty` (texte) et `header` (un élément au-dessus des items, comme le bouton de création).
- **`ProfileListScreen`** utilise `List` pour les listes décrites par le serveur. Son rendu actuel ne change pas :
  - le bouton de création en haut à droite ;
  - les items, l'édition, le retrait avec confirmation ;
  - le message de retour.
- Toute autre feature peut utiliser `List` avec ses propres données.

## Critères de succès

1. Un compte qui a le rôle `admin`, et lui seul, voit, crée, modifie et retire des adresses dans le back-office. Rejouer le seeder ne retire pas une permission ajoutée à la main au rôle.
2. Avec 45 adresses, `GET /api/v1/lists/location.addresses` sert 20 items et un `next_cursor`. Suivre les curseurs rend les 45 adresses une seule fois, dans l'ordre, et la dernière page a `next_cursor: null`.
3. Retirer une adresse entre deux pages ne fait ni sauter ni répéter d'item.
4. Un curseur invalide ne produit pas d'erreur 500.
5. Dans l'app, faire défiler la liste charge la suite. Elle n'envoie qu'une requête à la fois et s'arrête quand il n'y a plus rien.
6. `List` est testée seule, avec des données qui ne viennent pas du serveur.
7. Les tests de `ProfileListScreen` passent, adaptés à la pagination.
8. Les tests web passent, Pint est propre et `openapi.json` est à jour. Les tests mobiles passent, `tsc` et le lint sont propres.

## Hors périmètre

- Recherche ou filtre dans la liste.
- Un nombre total d'items affiché.
- Donner d'autres permissions au rôle `admin`.
