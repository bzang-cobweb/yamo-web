# Recherche dans la liste

Demande du 2026-11-18, après le chantier `2026-11-18-liste-infinie-role-admin` :

> a jouter une recherche dans la liste

## Ce que j'ai compris

Les listes servies à l'app (« Mes adresses » aujourd'hui, toute liste demain) gagnent un champ de recherche. La liste étant paginée, la recherche se fait **sur le serveur** : filtrer dans l'app ne verrait que les pages déjà chargées.

### Serveur

- `GET /api/v1/lists/{name}?search=…`, combinable avec `?cursor=` : les pages suivantes suivent la même recherche.
- La recherche est insensible à la casse. `%` et `_` sont cherchés tels quels, sans servir de jokers.
- Les espaces superflus sont retirés. Une recherche vide ou faite d'espaces sert toute la liste.
- **Chaque liste choisit si elle est cherchable.**
  - Une `Listing` déclare `search(Builder $query, string $term)`. Par défaut, il n'y en a pas, et la liste ignore `?search=`.
  - La réponse gagne `search` : le texte d'aide du champ (`« Rechercher une adresse… »`), ou `null` pour une liste non cherchable. L'app n'affiche le champ que si `search` n'est pas `null`.
- **« Mes adresses »** cherche dans :
  - le destinataire : prénom, nom ;
  - l'adresse : rue, NPA, ville.

  Un item correspond si l'un de ces champs contient le texte.
- La documentation OpenAPI est régénérée.

### App

- Le composant générique `List` gagne une prop optionnelle `search`, qui contient la valeur, le changement et le texte d'aide. `List` affiche alors un champ au-dessus des items, hors de la zone qui défile, pour que le clavier ne se ferme pas à chaque résultat. Toute feature peut s'en servir.
- `ProfileListScreen` :
  - affiche le champ si l'API sert `search` ;
  - envoie la recherche 300 ms après la dernière frappe ;
  - repart de la première page à chaque nouvelle recherche ;
  - garde le défilement infini sur les résultats.
- **Aucun résultat** :
  - un texte de l'app, servi par l'API comme les autres (`list.noResults` : « Aucun résultat. ») ;
  - distinct du texte de la liste vide.
- Le bouton de création reste affiché pendant une recherche.

## Critères de succès

1. Avec des adresses variées, chercher `genè`, `GENÈVE`, `1200`, un prénom ou un nom ne rend que les adresses du compte qui correspondent, dans l'ordre de la liste.
2. Les adresses d'un autre compte ne sortent jamais.
3. Avec plus de 20 résultats, les curseurs suivent la recherche jusqu'au dernier résultat : chaque item revient une seule fois et la dernière page a `next_cursor: null`.
4. `%`, `_` ou un texte vide ne cassent rien : pas de 500, pas de joker, et un texte vide sert toute la liste.
5. Une liste sans `search()` sert `search: null` et ignore `?search=`.
6. Dans l'app :
   - taper dans le champ envoie une seule requête après la pause ;
   - effacer le champ rend toute la liste ;
   - aucun résultat affiche « Aucun résultat. » ;
   - le clavier reste ouvert pendant que les résultats arrivent.
7. Tous les tests passent :
   - web : les tests, Pint propre, `openapi.json` à jour ;
   - mobile : les tests (dont `List` avec recherche, sur données locales), `tsc` et le lint propres.

## Hors périmètre

- La recherche dans le back-office (Filament a déjà la sienne).
- Les filtres par champ, le tri choisi par l'utilisateur et le surlignage du texte trouvé.
- La recherche plein texte ou approximative (fautes de frappe).

## Ce que j'ai décidé seul

- **Caractère d'échappement `!` dans le `LIKE`** (`ESCAPE '!'`) : le seul qui soit compris de la même façon par MariaDB et par SQLite, celui des tests. Avec `\`, SQLite refuse l'échappement.
- **Une méthode et un texte d'aide.** Une liste est cherchable quand `searchPlaceholder()` rend un texte, et `search()` dit comment chercher. La classe de base fournit `orWhereContains()` pour que chaque liste ne refasse pas l'échappement.
- **Les items précédents restent affichés** pendant qu'une nouvelle recherche se charge (`keepPreviousData`) : le champ ne disparaît pas et le clavier reste ouvert.
- **« Aucun résultat. » s'affiche dès qu'une recherche est active** et que la liste est vide.

## Ce qui a été fait

- **Web** (commit `e40d556`) :
  - `?search=` passe à `Listing::page()` ;
  - la réponse porte le champ `search` (texte d'aide ou `null`) ;
  - « Mes adresses » cherche dans le prénom, le nom, la rue, le NPA et la ville ;
  - textes ajoutés en fr et en : `list.search` et `list.noResults` ;
  - OpenAPI régénéré ;
  - tests : correspondances, autre compte, `%` et `_`, texte vide, curseurs sur la recherche, liste non cherchable.
- **Mobile** (commit `bd30e29`) :
  - `List` reçoit la prop `search`, avec le champ placé hors de la `FlatList` ;
  - `ListService.get(name, cursor, search)` ;
  - `useListDefinition(name, search)` ;
  - `ProfileListScreen` envoie la recherche 300 ms après la dernière frappe ;
  - tests et README.
- **Contrôles** : 319 tests web, Pint propre, `openapi.json` à jour. 169 tests mobile, tsc et lint propres. L'échappement est vérifié sur MariaDB.
- **QA** (`bza-dev:qa-check`) : GO, rejoué sur MariaDB. Détail dans `.ia/runs/2026-11-18-recherche-dans-la-liste/qa-check.md`.

## Ce que je n'ai pas vérifié

- **Le clavier et le délai de frappe sur un appareil ou un simulateur.**
- **Les performances sur un gros volume** : `LIKE '%…%'` ne profite d'aucun index.

## Ce qui reste en suspens

- **Comportement selon la base** : sur MariaDB, la recherche ignore aussi les accents (« ERIC » trouve « Éric ») ; dans les tests, sur SQLite, non.
- **Prénom et nom ensemble** : « jeanne dupont » ne trouve rien, car chaque champ est comparé séparément. C'est conforme au contrat.
- **Recherche en échec** : l'écran passe à l'état d'erreur et le champ disparaît, mais le texte saisi est gardé. Il revient avec « Réessayer ».
- **Rien n'est poussé.**
