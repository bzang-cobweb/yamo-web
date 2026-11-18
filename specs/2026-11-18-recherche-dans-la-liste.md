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
