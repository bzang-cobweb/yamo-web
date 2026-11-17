RAPPORT QA — 2026-11-20-suppression-de-compte (+ 2 retouches)
Go : non
Base du verdict : rejoué moi-même.
- web : `ddev artisan test` 213 passed (996 assertions) ; `pint --test` 277 fichiers PASS ; `schedule:list` : `0 0 * * * auth:purge-deleted-accounts` ; `migrate:status` : 2026_11_22_090000_add_soft_deletes_to_users [9] Ran ; `scramble:export` puis `diff` avec openapi.json : identique ; `config("auth.account_deletion.grace_days")` = 30 ; `.env.example` : +AUTH_ACCOUNT_DELETION_GRACE_DAYS=30 ; composer.json et composer.lock sans diff.
- mobile : `npx jest --forceExit` 26 suites, 93 tests passed ; `tsc --noEmit` sans sortie ; `expo lint` sans sortie ; package.json sans diff.
- Lecture du code (service, contrôleur, requête, formulaire, politique, commande, filtre, écrans app, layout des onglets).
- Sonde jetable (tinker, transaction annulée) : un compte supprimé en douce portant l'e-mail X, puis `Challenges::ensureFree('email','X')` -> aucun refus.
Ne couvre pas : rendu visuel réel sur appareil ou simulateur (espacement du sélecteur de langue, couleur rouge, barre du logo : lus dans le code et les tests, pas vus à l'écran) ; passage réel du planificateur à minuit ; purge réelle d'une photo (le code Spatie la supprime au forceDelete, mais aucun test ne le prouve) ; courses concurrentes en base.

Bloquants : 1
- Critère 6 : `Modules/Auth/app/Services/Challenges.php::ensureFree()` utilise `User::query()`, qui exclut les comptes supprimés. Or la colonne reste sous index unique. Appelée à la validation des codes (RegisterService l.65-67, EmailChangeService l.32, PhoneChangeService l.41), elle laisse passer une valeur tenue par un compte en attente de purge. Cas concret : un code d'inscription est envoyé pour X (libre) ; une autre personne s'inscrit avec X puis supprime son compte ; la première valide son code -> `ensureFree` ne refuse pas -> violation de l'index unique, erreur de base de données (500) au lieu d'une erreur 422 propre. Cette fonction existe précisément pour éviter « de buter sur l'index unique » (son commentaire). `UniquePhone` a été corrigée avec `withTrashed()`, pas `ensureFree`. Correction : `User::withTrashed()` dans `ensureFree`, plus un test. Preuve : sonde ci-dessus, rejouable avec `ddev artisan tinker`.

Majeurs : 0

Mineurs : 2
- Critère 4 : aucun test ne vérifie que la purge supprime la photo (`test_the_purge_removes_only_accounts_past_the_configured_delay` ne regarde pas les médias). Le comportement est correct d'après `vendor/spatie/laravel-medialibrary/.../InteractsWithMedia.php` l.56-62.
- Critère 6 : le test de réservation ne couvre que l'e-mail via l'inscription ; ni pseudonyme, ni téléphone, ni changement d'e-mail ne sont testés après suppression.

Critères sans écart relevé : 1 (formulaire `auth.profile.delete-account`, délai cité, mot de passe, bouton rouge `destructive`, lien rouge) ; 2 (204, 422 mauvais mot de passe, 401 invité, jetons supprimés, login refusé ; app : message puis `signOut`) ; 3 (dernier super admin refusé avec message ; autorisé s'il en reste un autre) ; 4 hors photo testée (config, .env.example, planification quotidienne, seuil 31 j purgé / 29 j conservé) ; 5 (TrashedFilter, restore et forceDelete sous permission de suppression, `forceDelete` et `delete` refusés sur soi-même) ; 7 (marge sous le sélecteur de langue ; barre sur Accueil et Profil, `headerShown: !inSubPage` pour les formulaires, aucune barre sur l'accueil invité) ; 8 hors point 6.


## Deuxième passage

RAPPORT QA — 2026-11-20-suppression-de-compte, deuxième passage
Go : oui
Base du verdict : rejoué moi-même.
- web : `ddev artisan test` 216 passed (1013 assertions) ; `pint --test` 277 fichiers PASS ; `scramble:export --path=/tmp/openapi-check.json` puis `diff` avec openapi.json : identique.
- Sonde jetable (tinker, transaction annulée, script hors dépôt) : un compte supprimé portant e-mail, pseudonyme et téléphone ; `Challenges::ensureFree` refuse les trois (ValidationException sur `code`, donc 422) ; avec `$ignoreUserId` du compte lui-même, pas de refus. Le blocant du premier passage est levé. `ensureFree` utilise maintenant `User::withTrashed()` (diff : 3 lignes).
- Validation des formulaires : test `test_the_username_phone_and_email_stay_reserved_until_the_purge` (inscription : pseudonyme et téléphone ; changement d'e-mail) et `test_the_email_stays_reserved_until_the_purge` (inscription : e-mail), verts : 422 avec erreurs de validation.
- Validation d'un code : `test_a_value_held_by_a_deleted_account_awaiting_purge_is_refused_cleanly` : 422 sur `code`, aucun compte créé.
- Mineurs : photo à la purge couverte par `test_the_purge_deletes_the_photo_too` (média 1 avant purge, 0 après, disque vide) et `test_the_photo_stays_until_the_account_is_purged` ; réservation pseudonyme, téléphone, changement d'e-mail couverte au niveau formulaire (voir ci-dessus).
- Autres critères : relu seulement le diff Challenges.php et les tests ; aucun des critères 1 à 5, 7, 8 n'est touché ; les tests correspondants restent verts. Mobile non rejoué (déclaré inchangé ; verdict du premier passage : jest 93 passed, tsc et lint propres).
Ne couvre pas : rendu visuel sur appareil ; passage réel du planificateur à minuit ; courses concurrentes en base ; mobile non rejoué ; mutation du correctif (retirer `withTrashed` et constater l'échec du test) non faite, car interdit de modifier un fichier suivi ; la sonde en tinker prouve le comportement directement.

Bloquants : 0

Majeurs : 0

Mineurs : 1
- Au niveau de la validation d'un code, seul l'e-mail via l'inscription est testé. Pseudonyme et téléphone via `ensureFree`, ainsi que les codes de changement d'e-mail et de téléphone, ne le sont que par ma sonde, pas par un test du dépôt. Non bloquant : la fonction est unique et partagée.
