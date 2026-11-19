RAPPORT QA — 2026-11-19-paiement-visibilite-avis
Go : oui
Base du verdict : rejoué moi-même — `ddev artisan test --compact` : 505 passés (2472 assertions) ; mobile `npx jest --forceExit` : 44 suites / 235 tests verts ; `npx tsc --noEmit` : aucune erreur ; `npm run lint` : aucune erreur. Lecture de `git show 1b13046` (web) et `f3d9dd4` (mobile) ; grep de `paid_until` (seules les anciennes migrations et le contrat/spec en parlent ; plus rien dans app/mobile) ; grep des filtres « publiée + pays » dans app/Modules (hors Filament/tests) ; `migrate:status` : les deux nouvelles migrations sont « Ran ».
Ne couvre pas : exécution réelle de la migration sur des données (renameColumn natif, réversible à la lecture ; non rejoué, pas de migrate autorisé) ; rendu visuel du back-office et de l'app ; OpenAPI non régénéré (interdit) ; seulement ces deux commits.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- `OfferListing` (offer.mine) et `OfferForm` filtrent encore par pays à la main : légitime (auteur, ou formulaire de l'auteur), pas une montre à autrui. Aucune autre duplication de « publiée + pays » côté API : la règle est unique (`Offer::scopeVisibleToOthers` / `isVisibleToOthers`), utilisée par OfferPolicy (show, review, favorite), OfferBrowseListing (liste, épinglées, villes), OfferFavoritesListing ; la liste des avis passe par la policy `show`.
- Le test de visibilité ne vérifie pas la conservation des données de la migration de renommage (aucun test de migration) ; le fichier de spec apparaît déplacé vers specs/done/ dans l'index git (renommage non commité, hors livrable).

Critères : 1 avis par défaut via config `offer.reviews_shown` (OfferDetailResource) ; app : `count > latest.length` ; case « Appliquer le paiement » (Toggle, cast booléen, défaut vérifié par test ApplicationTest) ; paiement non appliqué : aucun filtre sur expires_at ; appliqué : expiré ou null => 404 fiche/avis/favori, absent accueil/villes/favoris, auteur garde fiche et offer.mine (test OfferVisibilityApiTest) ; renommage expires_at « Date d'expiration » fr/en. Tous satisfaits.
