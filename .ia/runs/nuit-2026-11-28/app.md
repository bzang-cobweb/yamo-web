# Nuit 2026-11-28 — connexion-panneau, partie APP MOBILE

Dépôt `yamo/mobile`, branche `nuit/2026-11-28`. Contrat : `specs/2026-11-28-connexion-panneau.md`.
Décision : `.ia/context/decisions/2026-11-28-app-connexion-panneau.md`.

## Fait

- `GuestTab` (un composant pour Profil et Notifications) : titre, sous-titre (`auth.guest.*Subtitle`), message, bouton
  `auth.signInOrUp` qui ouvre le panneau, langue.
- `AuthSheet` : `openAuthSheet()` / `AuthSheetHost` (dans `_layout.tsx`) ; `BottomSheet` taille maximale titré
  `auth.signInOrUp`, bandeau des messages par-dessus. Toutes les entrées vers `/sign-in` l'ouvrent (onglets, Réserver,
  bouton de section, lien servi `/sign-in`). Route `/sign-in`, `SignInScreen`, `GuestScreen` retirés.
- `AuthFlow` : « Bon retour » (`WelcomeBackStep` : `auth.welcomeBack`, identifiant masqué, `auth.welcomeNote.email|sms`,
  `auth.signIn`, `auth.notYou`) ou formulaire `auth.login` (canal présélectionné, indicatif mémorisé) ; `AuthCodeStep`
  (`auth.codeSentTo`, envoi dès 6 chiffres, `auth.didntGetIt` + `auth.sendNewCode`, `auth.tryAnotherWay` en panneau à
  la hauteur du contenu, `auth.codeBySms|codeByEmail` + masqué, retour) ; mot de passe selon `mode` et
  `auth.forgotPassword` ; inscription `auth.register?channel=` ; `RestoreAccountPrompt` gardé.
- Services : `LoginService.login` → `Challenge` (202) ; `VerificationService` mappe `{next:'password', challenge, mode}` ;
  `SessionService.open` (`POST /v1/auth/session`) ; `RegisterService.register(challenge, values, device)` → session.
- Mémoire `utils/last-sign-in.ts` (lecture, écriture après connexion, effacement ; indicatif à part).
- Formulaires : code complet → envoi automatique ; `defaults` et `params` sur `FormLoader`/`DynamicForm`.
- Textes de test (`test-messages.ts`) : nouvelles clés en français, insérées.

## Preuves

- `npx tsc --noEmit` : code de sortie 0.
- `npx jest --watchAll=false --forceExit` → `Test Suites: 76 passed, 76 total`, `Tests: 489 passed, 489 total`
  (journal : scratchpad `jest-app28.log`). Avant : 74 suites / 470 tests verts.
- Tests ajoutés : `features/auth/__tests__/AuthSheet.test.tsx` (12 : panneau titré à taille maximale et croix, parcours
  sans mémoire jusqu'à connecté + mémoire écrite, code envoyé au 6e chiffre, mot de passe oublié avec `reset`, modes
  `create` et `register` + inscription `?channel=phone`, restore, « Bon retour » e-mail et SMS, retour, « Ce n'est pas
  vous ? » + indicatif mémorisé, nouveau code + bandeau, « Essayer autrement » dans les deux modes),
  `last-sign-in.test.ts` (5), `form/__tests__/CodeAutoSubmit.test.tsx` (3) ; `challenge-services`, `GuestTabs`,
  `OfferDetailScreen`, `PartnerScreens`, `PartnersStrip` mis à jour ; `SignInScreen.test` retiré.

## Commits (mobile)

- `6a1fbed` Formulaires : code complet envoie le formulaire ; `defaults` et `params`
- `9eba6bf` Connexion : services (login 202, verify → password + mode, session, register avec défi) ; mémoire ; `/sign-in` retiré
- `f7fbcfd` Se connecter ou s'inscrire dans un panneau ouvert de partout (parcours complet)
- `99e48a4` Textes de test : sous-titres alignés sur le serveur

## Hypothèses sur l'API (à confronter au serveur)

1. `POST /auth/login` répond toujours `{challenge, channel: 'email'|'sms', destination}` ; l'app envoie pour le
   téléphone mémorisé `{channel:'phone', phone: <E.164>, phone_country}` — le serveur doit accepter un E.164 avec pays.
2. `codes/verify` envoie encore `channel` (inchangé) ; `mode` absent est lu comme `sign-in`.
3. `POST /auth/session` : `{challenge, password, password_confirmation?, reset: true (seulement si oublié), device_name}`
   → `{data, token}` ou `{next:'register', challenge}` ; erreurs 422 sur `password`.
4. `GET /forms/auth.register?channel=email|phone` : `channel` = canal du défi (`sms` → `phone`).
5. `POST /auth/register` : `{challenge, first_name, last_name, email|phone+phone_country (vides si non remplis),
   device_name}` → 201 `{data, token}`.
6. `UserResource` renvoie `email_masked`, `phone_masked`, `phone_country` (mémoire et « Essayer autrement »).
7. Le formulaire `auth.login` a un champ `channel` (segmented) et `phone` (type phone, `phone_country`) : présélection
   par les noms de champs.

## Non vérifié

- Rien sur simulateur ni contre le vrai serveur (API simulée) ; rendu visuel du panneau, du clavier dans le panneau
  et du second panneau « Essayer autrement » posé dans le premier (Modal imbriqué) non vus.
- `restore` : la mémoire n'est pas mise à jour après un compte récupéré.
- L'envoi automatique du code s'applique aussi aux codes du profil (voulu, non revu à l'écran).
