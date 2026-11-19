RAPPORT QA — 2026-11-19-vedette-et-favoris (vérification finale)
Go : non (1 bloquant)

Base du verdict (rejoué moi-même) :
- Serveur : `ddev exec php artisan test Modules/Offer Modules/Form Modules/Auth tests/Feature` -> 429 passés, 2034 assertions, exit 0 (storage/app/qa-server.txt).
- App : `npx jest` -> 38 suites, 210 tests passés (storage/app/qa-jest.txt) ; `npx tsc --noEmit` exit 0 ; `npm run lint` exit 0.
- Lecture du diff complet des deux dépôts (policy, routes, contrôleur, listes, Listing::pinned/pinnedItems, ListingResource, ListItem, FavoriteHeart, ItemPicture, ProfileListScreen, ListService).
Ne couvre pas : simulateur / rendu réel du cœur et de l'ordre ; migration sur la base locale ; openapi.json régénéré (non rejoué, `composer openapi` interdit) ; textes fr/en vérifiés par lecture partielle seulement (clés offer.favorites.*, featured*, mobile.php présentes dans le diff, pas de test de parité rejoué à part) ; le scénario >20 vedettes est établi par lecture du code, pas par un test exécuté (aucun test existant ne le couvre, et je n'ai pas le droit d'en écrire dans le dépôt).

Bloquants : 1
- Offres perdues au-delà de 20 vedettes. `OfferBrowseListing::query()` exclut TOUTES les offres en vedette (`notFeatured()`), alors que `pinned()` n'en renvoie que `pinnedLimit()` = 20 (`Listing::pinnedItems`, `->limit(20)`). La 21e vedette (et suivantes) n'apparaît ni en tête ni dans les pages : elle disparaît de la vitrine tant qu'elle est en vedette. Le contrat n'impose aucune limite (« limite au nombre d'offres en vedette » est hors périmètre), donc rien n'empêche un admin d'en fixer 21. Critère 1 (« aucune offre perdue ») non tenu. Correction possible : ne pas limiter les épinglées (ou limiter ET laisser les excédentaires dans la requête paginée), ou plafonner côté back-office.

Majeurs : 2
- `ListService.setFavorite` ne valide que `^/v1/[^?#]*$` et l'absence de `..` : un `favorite.endpoint` pourrait faire émettre PUT/DELETE vers n'importe quelle route `/v1/...` (ex. une route de suppression). Critère « un endpoint de favori ne doit pas permettre d'appeler une autre route » non tenu côté app ; la source est le serveur (de confiance), mais le contrat annonçait la validation. Restreindre à `^/v1/offers/[0-9]+/favorite$`.
- `link` : la validation app `^/[a-z0-9/_-]+$` laisse ouvrir n'importe quel écran interne (`/profile/...`, `/sign-in`, et `//x` passe aussi). Pas d'URL externe (le `.` et `:` sont exclus), mais « écran arbitraire » possible. Restreindre à `^/offers/[0-9]+$`.

Mineurs : 4
- Aucun test pour : plus de 20 vedettes, super administrateur (la policy l'exclut bien du contournement par `before`, vérifié à la lecture seulement), N+1 (aucune assertion de nombre de requêtes sur browse/favorites).
- Fenêtre transitoire : une offre qui expire ou devient vedette entre la page 1 et la page 2 peut être doublée ou sautée (`now()` évalué à chaque requête). Acceptable, à documenter.
- Les épinglées sont réévaluées à chaque appel de première page ; `pinned()` sans pagination ni plafond de coût, d'où le bloquant ci-dessus.
- Phpdoc de `ListingResource::toArray` (@return) n'indique pas les clés `rating/highlight/favorite/link` ; openapi.json à rejouer par l'équipe.

Points vérifiés conformes (lecture + tests passés) :
- Critère 1 : champ/colonne/filtre Filament + test ; tri en tête, expirée reprend sa place ; épinglées exclues de la requête paginée (pas de doublon page 2, test dédié) ; recherche appliquée aux épinglées (`pinnedItems` + `search()`), curseur => aucune épinglée sur les pages suivantes ; aucun badge.
- Critères 2 à 6 : cœur seulement si membre connecté et pas sa propre offre (browse, fiche) ; absent de offer.mine (testé) ; PUT/DELETE 403/404/401 testés ; archivée disparaît/revient (testé) ; super admin non exempté de `favorite` (before renvoie null) ; `link` posé en browse et favoris, géré dans ProfileListScreen ; mise à jour optimiste, retour arrière et Alert dans FavoriteHeart.
- Pas de N+1 apparent : `withExists` pour is_favorite, eager `address/media/reviewSummary` dans browse, pinned et favoris ; la fiche fait 1 requête exists.

=====================================================================
REVÉRIFICATION APRÈS CORRECTIONS — VERDICT FINAL
Go : oui (0 bloquant, 0 majeur)

Rejoué : serveur (Modules/Offer Modules/Form Modules/Auth tests/Feature) 430 passés, 2037 assertions, exit 0 (storage/app/qa-server2.txt) ; jest 38 suites / 210 tests passés ; tsc exit 0 ; lint exit 0. (jest : même nombre de tests, les assertions ont été ajoutées dans des tests existants, vérifié dans FavoriteHeart.test.tsx l.70-71 et ProfileListScreen.test.tsx l.181-183.)

- Bloquant (>20 vedettes) : levé. `pinnedIds()` (20 premières vedettes, hors recherche, mémorisées par instance) ; `pinned()` = ces ids filtrés par la recherche ; `query()` n'exclut que ces ids. Une vedette au-delà de 20 reste dans la liste paginée. Test `test_beyond_the_pinned_limit_a_featured_offer_stays_in_the_list` : 27 ids uniques, 20 premières en tête, passe.
- Recherche : pas de doublon. Les ids épinglés sont indépendants de la recherche, donc une épinglée qui correspond n'apparaît qu'en tête (page 1) et est exclue des pages ; une vedette hors des 20 qui correspond n'apparaît qu'une fois, dans la liste paginée. Établi par lecture du code ; aucun test ne combine recherche + >20 vedettes (mineur).
- Majeur setFavorite : levé, `^/v1/[a-z-]+/\d+/favorite$`, refus testés (/v1/auth/me, /v1/offers/7, ../, URL externe). Reste large sur le segment ressource (toute route `/v1/<x>/<n>/favorite`), acceptable.
- Majeur link : levé, `ITEM_LINK = ^/[a-z-]+/\d+$`, test /profile/delete-account ignoré. Reste large sur le premier segment (ex. `/profile/12`), mineur.
- Mineur phpdoc : non revérifié en détail.

Mineurs restants : fenêtre transitoire entre pages (ids épinglés recalculés à chaque requête) ; pas de test super administrateur ni N+1 ; pas de test recherche + >20 vedettes.
Ne couvre pas : simulateur, openapi.json régénéré, migration sur base locale, parité fr/en exhaustive.
