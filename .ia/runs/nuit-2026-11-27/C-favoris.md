# Lot C — Favoris (nuit du 2026-11-27)

Contrat : `specs/2026-11-27-visiteur-profil-favoris.md`, lot C (C1–C4). Décisions :
`.ia/context/decisions/2026-11-27-listes-de-favoris.md`, `.ia/context/decisions/2026-11-27-favoris-menu-a-droite.md`.

## Ce qui est fait

- **C1 — listes nommées** : tables `wishlists` (`user_id`, `name`, `kind` custom|recent) et `wishlist_items`
  (migration `2026_12_12_100000`), migration de données `2026_12_12_110000` (favoris d'avant → liste « Favoris » par
  membre, dans sa langue, rejouable). `offer_favorites` **gardée mais plus lue ni écrite**.
  Point d'API du cœur inchangé (`/v1/offers/{id}/favorite`) : `GET` = panneau (titre, listes nommées, textes de
  création), `PUT {wishlist}` ou `PUT {name}` (création à la volée), `PUT` nu = première liste (compat app ancienne),
  `DELETE` = retrait de toutes les listes nommées. App : cœur vide → panneau à la hauteur du contenu
  (`WishlistPicker`, « Créer une liste » en bas → nom → « Créer ») ; cœur plein → retrait aussitôt.
- **Récemment consultés** : une par membre, créée à la demande, remplie par `POST /v1/offers/{id}/click` d'un membre
  connecté (pas sa propre offre), dernière ouverte en tête, 50 au plus. Absente du panneau du cœur.
- **« Mes favoris »** (lien du menu → liste `offer.wishlists`) : « Récemment consultés » puis les listes nommées, nom,
  « N offres », petite photo ; toucher → `/wishlists/{id}` (liste `offer.wishlist`, filtre caché `wishlist`).
- **C2** : liste nommée ouverte → menu ⋮ dans la barre du haut (**à droite**, décision dédiée) : « Renommer »
  (formulaire servi `offer.wishlist`, `PATCH /v1/wishlists/{id}`), « Supprimer » (confirmation,
  `DELETE /v1/wishlists/{id}`, retour à « Mes favoris »).
- **C3** : bloc « Récemment consultés » à l'accueil avant « À la une » (membre connecté, au moins une offre ; 10 cartes
  en rangée qui défile ; flèche « Voir tout » → la liste). Servi par `GET /v1/wishlists/recent`, rechargé à chaque
  retour sur l'accueil.
- **C4** : liste « Récemment consultés » en deux cartes par ligne, croix sur chaque photo qui retire l'offre
  (`DELETE /v1/wishlists/{id}/offers/{offer}`) sans confirmation.
- Paquet `form` (ajouts compatibles) : `Listing::columnsFor()`, `Listing::headerActionsFor()`/`headerAction()`,
  `ListItem::delete(…, onImage:)` ; clés servies seulement quand utilisées (`ListingApiTest` exact inchangé).
- Mobile, ajouts compatibles : `List` (`columns`), `BottomSheet` (`keepTouches` : les touchers du panneau ne remontent
  pas à la carte qui porte le cœur), `ItemPicture` (croix), `ListItemCard`, `ProfileListScreen` (menu ⋮ servi,
  colonnes, retrait sans confirmation pour la croix).
- Démo (`DemoSeeder`, section favoris) : « Favoris » (3 offres), « Vacances » (1), 4 fiches consultées.
- Demande du coordinateur : icônes des liens du menu du profil du module Offer (réservations, favoris, logements).

## Preuves

- Web : `ddev exec php artisan test --compact Modules/Offer packages/form/tests tests/Feature/RouteGuardTest.php
  tests/Feature/Form` → **396 passed** (2827 assertions), après le commit de l'autre session ;
  `WishlistApiTest` 9 tests (panneau, création à la volée, refus d'une liste d'un autre / de « récente » / nom trop
  long, « Mes favoris », menu ⋮ + renommer + supprimer, droits, récemment consultés + croix, limite 50, bloc de
  l'accueil, migration de données). Tests de traduction/messages : 37 passed.
- `ddev exec vendor/bin/pint --test` sur mes fichiers : PASS (Pint lancé fichier par fichier, pas `--dirty`, pour ne pas
  reformater les fichiers en cours de l'autre session).
- `ddev exec php artisan migrate` : les deux migrations passées ; base DDEV : 7 favoris copiés en 7 rangements.
- Mobile : `npx tsc --noEmit` sans erreur ; `npx jest --watchAll=false --forceExit` → **Test Suites: 72 passed, 72
  total ; Tests: 462 passed** (nouveaux : `FavoriteHeart.test.tsx` réécrit, `WishlistScreen.test.tsx`,
  `HomeScreen.test.tsx` complété).

## Commits

- web `1b859a3` — Favoris en listes nommées (back, paquet form, tests, démo).
- web `65a98ab` — Icônes du menu du profil (Offer) ; décisions.
- mobile `e12e372` — Favoris en listes (panneau du cœur, liste ouverte, menu ⋮, deux colonnes et croix, accueil).
- web (ce rapport) — commit suivant.

## Décidé seul

- Menu ⋮ à droite (la gauche porte le retour) — décision `2026-11-27-favoris-menu-a-droite`.
- La croix de « Récemment consultés » retire sans confirmation ; ni cœur ni menu sur cette liste.
- « Récemment consultés » toujours visible dans « Mes favoris » (créée à l'ouverture de « Mes favoris » si besoin).
- Bornes : 50 offres gardées, 10 sur l'accueil ; nom de liste 60 caractères.
- Bloc de l'accueil servi par un point d'API à part (`/v1/wishlists/recent`), pas dans `/v1/home`.
- Le cœur sans liste choisie (`PUT` nu, app ancienne) range dans la première liste nommée, « Favoris » créée au besoin.
- La liste `offer.favorites` disparaît, remplacée par `offer.wishlists` (les listes) et `offer.wishlist` (une liste).
- `MenuShell` local dans `ProfileListScreen` (copie de `ProfileScreenShell` avec `right`) : `ProfileFormScreen.tsx`
  est hors de mon périmètre ; à fusionner en ajoutant `right` à `ProfileScreenShell`.

## Non vérifié

- Rien n'a été vu dans le simulateur (consigne) : panneau du cœur, menu ⋮, deux colonnes, bloc de l'accueil.
- La route `src/app/wishlists/[id].tsx` n'est pas déclarée dans `src/app/_layout.tsx` (hors périmètre) : Expo Router
  l'ajoute seul, sans la garde `Stack.Protected` (l'API répond 401 à un visiteur). À déclarer dans le groupe protégé.
- Les touchers d'un `Modal` remontant à la carte parente : parés par `keepTouches`, non vérifié sur appareil.
- `openapi.json` non régénéré (`ddev composer openapi`) : il aurait mêlé les changements en cours de l'autre session.
- `ddev exec php artisan migrate` a aussi passé la migration en attente de l'autre session
  (`2026_12_08_100000_make_password_nullable_on_users`).
- `offer_favorites` : à supprimer plus tard par une migration, une fois les listes validées.
