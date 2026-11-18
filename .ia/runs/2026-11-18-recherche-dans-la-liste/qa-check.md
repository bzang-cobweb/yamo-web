RAPPORT QA — 2026-11-18-recherche-dans-la-liste
Go : oui
Base du verdict : rejoué moi-même.
- web : `ddev exec php artisan test` 319 passed (1559 assertions) ; Pint --test : 328 fichiers PASS ; `scramble:export --fail-on-unknown` vers storage/app/qa-openapi.json puis cmp avec openapi.json : identique.
- mobile : jest 33 suites / 169 tests verts ; `tsc --noEmit` sans erreur ; lint 0 erreur, 1 warning (router.d.ts, fichier généré, hors commit bd30e29).
- MariaDB 11.8.6 locale DDEV (collation utf8mb4_general_ci), script jetable en transaction annulée (0 reste @example.test), requêtes HTTP via le noyau avec jeton Sanctum, 2 comptes :
  - `genè`, `GENÈVE`, `genève`, `1200`, `lac`, prénoms et noms (`paul`, `MARTIN`, `léa`, `ÉRIC`) : seules les adresses du compte, ordre de la liste, 0 adresse étrangère (autre compte portant les mêmes adresses et noms).
  - 101 adresses (51 correspondent à `lac`) : pages 20/20/11, dernière `next_cursor` null, ordre attendu, aucun doublon. Liste complète sans recherche : 54/54, pagination inchangée.
  - `%` -> seul « 100% Bio » ; `_` -> seul « Under_score » ; `%%`, `x_y`, `!`, `!%`, `\`, `'; DROP TABLE users;--` : 200, 0 résultat, pas de joker, pas d'injection. Vide, `   ` : 20 items + curseur (toute la liste). `search[]=x`, `search[]=`, `search[a]=b` : 200, liste complète.
  - Curseur issu de `search=lac` rejoué avec `search=lausanne`, `zurich` ou sans recherche : 200, 0 adresse étrangère, aucun 500.
- Critère 5 : couvert par test_a_list_without_search_ignores_it (vert) et `search: null` (liste de test).
- Critère 6 : lu le code et les tests. Champ hors de la FlatList (View parent), debounce 300 ms testé en fake timers (une seule recherche, `['', 'visa']`), effacer -> `['', 'visa', '']`, « Aucun résultat. » via `list.noResults` servi par l'API (fr/en), `keepPreviousData` dans useListDefinition (pas de retour à l'état de chargement).
Ne couvre pas : comportement réel sur appareil/simulateur (clavier réellement maintenu ouvert, ressenti du debounce) ; les tests d'écran mockent useListDefinition, la non-régression du chargement repose sur `keepPreviousData` et son test de hook, que je n'ai pas rejoués hors de la suite jest ; MariaDB avec une autre collation ; très gros volumes (performance du `LIKE '%…%'` sur LOWER(), sans index).

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Sur MariaDB (collation _general_ci) la recherche est aussi insensible aux accents (`ERIC` trouve « Éric », vraisemblablement `geneve` trouve « Genève ») ; SQLite ne l'est pas. Comportement différent selon la base, non exigé par le contrat.
- Une recherche multi-champs (`jeanne dupont`) ne trouve rien : chaque champ est testé séparément, conforme au contrat (« l'un de ces champs contient le texte ») mais peut surprendre.
- Si une requête de recherche échoue (isError), ProfileListScreen remplace tout l'écran par l'état d'erreur et le champ disparaît avec le texte saisi ; non couvert par le contrat.
