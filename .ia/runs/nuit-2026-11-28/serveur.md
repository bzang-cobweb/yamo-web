# Nuit 2026-11-28 — connexion-panneau, partie SERVEUR

Dépôt `yamo/web`, branche `nuit/2026-11-28`. Contrat : `specs/2026-11-28-connexion-panneau.md` (section « API »,
points 1 à 9). Décision : `.ia/context/decisions/2026-11-28-connexion-panneau.md` (remplace en partie
2026-11-27-connexion-sans-mot-de-passe, marquée en tête).

## Fait

1. `GET /forms/auth.login` : inchangé dans sa forme (`channel` segmented si deux canaux, `email`, `phone` +
   `phone_country`) ; le téléphone propose désormais **tous les pays** avec leur indicatif, défaut : le pays courant.
   `PhoneNumbers::countryOptions()` et `PhoneNumbers::country()` ne filtrent plus les pays actifs : tous les champs
   téléphone de l'API suivent (profil, numéros, contact d'urgence, offre).
2. `POST /auth/login` → **toujours 202** `{challenge, channel, destination}` et un code envoyé. Connu → défi `LOGIN` ;
   inconnu ou compte inactif par SMS → défi `REGISTER` sans compte, `payload` = `{email}` ou `{phone, phone_country_id}`.
   Numéro jamais vérifié d'un compte actif : toujours `LOGIN` (devient vérifié au code).
3. `POST /auth/codes/verify` (`LOGIN`/`REGISTER`) → 200 `{next: 'password', challenge, mode}` (`sign-in` | `create` |
   `register`), défi ouvert et marqué vérifié ; `email_verified_at`/`phone_verified_at` posés pour l'identifiant prouvé ;
   compte en attente de suppression : `next: 'restore'` inchangé (puis `POST /auth/account/restore`, inchangé).
4. Formulaires `auth.session.password` (`password` ; « Votre mot de passe », « Se connecter ») et
   `auth.session.new-password` (`password`, `password_confirmation`, `Password::defaults()` ; « Choisissez un mot de
   passe », « Continuer »), publics, inscrits dans `AuthServiceProvider`.
5. `POST /auth/session` (`SessionController`/`SessionService`, `throttle:auth-login` dont la clé devient le défi quand
   il n'y a ni e-mail ni téléphone) : défi vérifié, non clos, moins de 30 minutes après le code
   (`auth.verification_codes.password_minutes`, `AUTH_CODE_PASSWORD_MINUTES`). `sign-in` juste → `{data, token}` et défi
   clos ; faux → 422 `password`, cinquième faux → défi clos ; `reset: true` ou `create` → nouveau mot de passe confirmé,
   `{data, token}` ; `register` → mot de passe haché dans le `payload` chiffré, `{next: 'register', challenge}`.
6. `GET /forms/auth.register?channel=email|phone` : `first_name` (Prénom), `last_name` (Nom), puis `phone` (type phone,
   tous les pays, facultatif, `UniquePhone`) si `email`, `email` (facultatif, unique) si `phone` ; « Créer votre compte »,
   « Créer mon compte ».
7. `POST /auth/register` (`RegisterService::register`) : défi `REGISTER` vérifié avec mot de passe → compte créé
   (identifiant vérifié, autre contact non vérifié, pseudonyme généré `Pseudonyms::generate`, langue de la requête,
   rôle membre), 201 `{data, token}`, défi clos. L'ancienne inscription par codes (`RegisterService::start/complete`)
   est retirée.
8. `POST /auth/codes/resend` : inchangé.
9. Textes `Modules/Auth/lang/{fr,en,de}/mobile.php`, section `auth` : `guest.profile` (nouveau texte),
   `guest.profileSubtitle`, `guest.notificationsSubtitle`, `welcomeBack`, `welcomeNote.email|sms`, `notYou`,
   `codeSentTo`, `didntGetIt`, `sendNewCode`, `tryAnotherWay`, `codeBySms`, `codeByEmail`, `forgotPassword`
   (`signInOrUp` et `guest.notifications` existaient).

Docblocks `@response` mis à jour (`LoginController`, `VerificationController`, `SessionController`,
`RegisterController`). `RouteGuardTest` : `api/v1/auth/session` ajoutée aux routes publiques motivées.

## Preuves

- `ddev exec php artisan test --compact Modules/Auth` → `Tests: 318 passed (2089 assertions)`.
- `ddev exec php artisan test --compact` → `Tests: 1021 passed (6814 assertions)` (aucun échec, `OfferPhotoApiTest`
  compris).
- `ddev exec vendor/bin/pint --dirty` → `PASS` (38 fichiers).
- Nouveau `Modules/Auth/tests/Feature/Api/SignInPanelApiTest.php` (22 tests) : login connu / inconnu / inactif par SMS
  → 202 même forme et bons défis ; identifiant invalide 422 ; verify → trois modes, défi ouvert, vérification posée ;
  restore + session refusée ; session juste (une seule fois), faux et cinquième essai, reset (autres appareils
  déconnectés), create, register (`next: register`, mot de passe haché), expiré (31 min), non vérifié / inconnu,
  limite de fréquence ; register par e-mail (téléphone non vérifié, langue, rôle, connexion ensuite par mot de passe),
  par téléphone (indicatif d'un pays inactif, e-mail facultatif), pseudonyme unique (`adamartin2`, `adamartin3`,
  `eve`), autre contact pris ou invalide, défi sans mot de passe / non vérifié / de connexion refusé, défi servi une
  fois et expiré, identifiant pris pendant le code ; formulaires servis (mot de passe, tous les pays, défaut).
- Aide de test `Modules/Auth/tests/Concerns/SignsUpWithCode.php`. Mis à jour pour le flux d'hier : `AuthApiTest`,
  `VerificationCodesApiTest` (section inscription retirée, couverte ailleurs), `RecycledPhoneTest`,
  `RegisterFormApiTest`, `MinimumAgeApiTest`, `AccountDeletionApiTest`, `AuthChannelsTest`.

## Commits

- `33c98bdd` Champs téléphone : tous les pays et leur indicatif, pas seulement les actifs
- `425f3db2` Connexion : identifiant, code, mot de passe, puis inscription (login 202, verify → password + mode,
  session, register, formulaires)
- `f0afa07f` Textes de l'app : panneau « Se connecter ou s'inscrire », invitations des onglets d'un visiteur
- `e2675614` Décision : connexion dans un panneau, côté serveur
- (ce rapport)

## Décidé seul

- Les refus liés au défi (inconnu, déjà servi, non vérifié, expiré, épuisé, sans mot de passe, identifiant pris pendant
  le code) portent sur **`code`**, comme `codes/verify`, `password/reset`, `account/restore` ; seul le mot de passe faux
  ou refusé porte sur `password`.
- Le compteur `attempts` du code est remis à zéro à la validation, puis compte les mots de passe faux.
- `reset: true` déconnecte les autres appareils ; `create` ne déconnecte personne.
- Pseudonyme : prénom + nom en ASCII minuscule sans signe, 30 caractères au plus (`membre` si vide), suffixe 2, 3… s'il
  est pris (comptes supprimés compris).
- Un identifiant d'inscription pris entre l'envoi et la validation du code est refusé dès le code (422 `code`), défi
  clos.
- `?channel=sms` vaut `phone` ; sans `channel`, le formulaire n'a ni téléphone ni e-mail.
- Mot de passe oublié d'hier (`auth.password.forgot|reset`, `POST /auth/password/forgot|reset`) laissé en place, sans
  usage dans l'app.
- Sous-titres des onglets visiteur (le contrat ne donnait pas le texte) : « Connectez-vous ou créez votre compte. »,
  « Vos demandes et vos messages arrivent ici. ».
- `openapi.json` non régénéré : `ddev composer openapi` échoue (10 erreurs Scramble préexistantes, Notification et
  ProfileMenuService) et réécrit ~8 000 lignes sans rapport ; fichier restauré.

## Écarts au contrat (à transmettre à l'app)

1. **Erreurs du défi sur `code`** (pas `password`) à l'étape du mot de passe et de l'inscription : l'app doit afficher
   `errors.code` (ou `message`) et revenir au début du parcours (le défi est clos ou expiré). Le mot de passe faux et
   le cinquième essai sont sur `password` (au cinquième, message « Trop d'essais. Recommencez la connexion. », défi
   clos).
2. **Âge minimum** : si un âge minimum est réglé au back-office, `auth.register` contient aussi `birth_date`
   (obligatoire, entre prénom/nom et l'autre contact) — l'app le rend comme tout champ servi. Sans réglage : exactement
   le contrat.
3. `POST /auth/register` est désormais **201** `{data, token}` (comme le contrat) ; l'ancienne réponse 202 d'inscription
   par codes n'existe plus.

## Non vérifié

- Pas d'appel réel depuis l'app (seulement les tests du serveur) ; la confrontation avec les hypothèses de
  `app.md` est faite à la lecture : points 1 à 7 compatibles, sauf les erreurs du défi (écart 1).
- Risque assumé noté dans la décision : l'autre contact non vérifié (numéro) d'une inscription sert à la connexion par
  SMS (règle du 2026-11-27 gardée) ; son vrai titulaire peut entrer dans ce compte par « Mot de passe oublié ? » et ne
  peut pas s'inscrire avec ce numéro. À trancher.
- `openapi.json` non à jour.
