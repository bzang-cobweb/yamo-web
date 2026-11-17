RAPPORT QA — 2026-11-20-restauration-numero-recycle
Go : non
Base du verdict : rejoué moi-même. `ddev artisan test` : 243 passés (1216 assertions). `pint --test` : propre (284 fichiers). `scramble:export` puis `diff` avec openapi.json : identique. `npx jest --forceExit` : 28 suites, 102 tests passés. `tsc --noEmit` : aucune sortie. `expo lint` : aucune sortie d'erreur. `AUTH_PHONE_INACTIVE_DAYS=180` présent dans .env.example (ligne 74). Aucun changement de composer.json, composer.lock, package.json ni package-lock.json (git diff côté mobile vide). Lecture du diff des deux dépôts, `PhoneOwnership`, `RegisterService`, `PhoneChangeService`, `VerificationCodeService` et `PasswordResetService`. Aucun test dédié de la course (grep « race|course » vide dans RecycledPhoneTest).
Ne couvre pas : course réelle en parallèle (non exécutée, constat par lecture du code) ; rendu visuel de l'écran mobile (testé par jest seulement) ; envoi réel de SMS ; planificateur de purge ; comportement avec une durée d'inactivité à 0 ou négative.

Bloquants : 1
- Critère 3, course : `RegisterService::complete` (l.66-72) et `PhoneChangeService::complete` enchaînent `ensureFree`, `PhoneOwnership::release` puis l'écriture (`save`) sans transaction, sans verrou et sans capture de l'erreur d'unicité. Aucun `QueryException` ni `DB::transaction` dans Modules/Auth/app, hors suppression de compte. La colonne `users.phone` a un index unique (migration 2026_11_17_110000). Scénario : A et B prouvent le même numéro, tenu par un compte inactif. Les deux passent `ensureFree`. A libère le numéro et insère son compte. B appelle `release`, qui trouve le compte neuf de A, actif, donc ne le libère pas. B insère et déclenche la violation d'unicité, soit une erreur 500 de base de données. Le critère dit « ne finit jamais en erreur de base de données ». Aucun test ne couvre ce cas. Même fenêtre pour `release` suivi d'un `save` qui échoue : le numéro est retiré de l'ancien compte sans être attribué.

Majeurs : 0

Mineurs : 2
- `PasswordResetService::reset` ne revérifie pas l'inactivité du compte à la fin du défi. Un défi SMS ouvert quand le compte était encore actif reste valide pendant la durée d'un code, soit quelques minutes. Fenêtre négligeable.
- `VerificationCode::user()` : le docblock `@return BelongsTo` est séparé de la méthode par un second docblock, qui est donc ignoré. Les types statiques perdent l'annotation générique.

Critères vérifiés conformes (preuve : tests rejoués plus lecture du code)
- 1 : `LoginService::complete` renvoie `{next: restore, challenge, deletion_date}` pour un compte supprimé. `AccountDeletionService::restore` n'accepte qu'un défi LOGIN validé depuis moins de la durée d'un code, sur un compte `trashed()`. Inconnu, non validé, expiré ou actif donne un 422. Un compte purgé reste introuvable. L'app : `RestoreAccountPrompt` propose « Récupérer mon compte » avec la date, ou « Annuler ».
- 2 : `PasswordResetService::start` neutralise le compte inactif par SMS (défi factice, rien d'envoyé). L'e-mail reste possible.
- 4 : aucune voie trouvée. Connexion et restauration exigent le mot de passe en plus du code. Le renvoi de code ne fait que renvoyer un code du même défi. Le changement de numéro exige d'être connecté. La réinitialisation par SMS est coupée pour un compte inactif.
- 5 : `UniquePhone` et `Challenges::ensureFree('phone')` passent par `PhoneOwnership::isTaken`. `release` et `isInactive` y vivent aussi.
- 6 : conforme, voir « Base du verdict ».

## Deuxième passage

RAPPORT QA — restauration-numero-recycle, deuxième passage
Go : oui
Base du verdict : rejoué moi-même. `ddev artisan test` : 244 passés (1223 assertions), 0 échec. `ddev exec vendor/bin/pint --test` : PASS, 284 fichiers. Export Scramble frais (`/tmp/openapi-check.json`) comparé à `openapi.json` : `diff` vide. Relu le diff de `Challenges.php`, `RegisterService.php`, `PhoneChangeService.php`, `EmailChangeService.php`, `VerificationCode.php` et le test `RecycledPhoneTest`.
- Critère 3 levé. `Challenges::persist` enveloppe la fin du défi dans `DB::transaction` et convertit `UniqueConstraintViolationException` en `ValidationException` sur `code`. Les trois services passent par lui, `ensureFree` et `PhoneOwnership::release` compris dans la transaction. Le retrait du numéro de l'ancien compte est donc annulé si l'attribution échoue.
- Le test de course insère un concurrent pendant `User::creating`, donc après `ensureFree`. Il passe : réponse 422 sur `code` (et non 500), aucun compte « nouveau », numéro toujours sur l'ancien compte.
- Mineur docblock levé : `VerificationCode::user()` documente le `withTrashed()`.
Ne couvre pas : vrai parallélisme (deux processus), seulement une course simulée dans une seule connexion. Pas de test de mutation (retirer `persist` pour vérifier que le test échoue), car je ne modifie aucun fichier suivi. Je n'ai pas relu les autres fichiers du chantier, ni l'app Expo, ni la base de développement.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- `persist` donne le même message (sur la colonne passée) pour toute violation d'unicité. À l'inscription avec e-mail seul ou avec téléphone, une collision sur une autre colonne, par exemple le nom, serait attribuée à `phone` ou `email`. Le message peut alors être trompeur, mais le refus reste propre.
- Le listener `User::creating` du test est enregistré sans être retiré. Il ne vise que le nom « nouveau », donc il est inoffensif aujourd'hui, mais il reste actif pour la suite du processus de test.
