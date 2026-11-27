RAPPORT QA — 2026-11-27-visiteur-profil-favoris
Go : oui
Base du verdict : rejeu indépendant le 2026-11-28 sur la branche nuit/2026-11-27.
- Web : `ddev exec php artisan test --compact` : 1008 passed (6499 assertions), 0 échec, 72 s (pas d'intermittence OfferPhotoApiTest).
- Pint : `vendor/bin/pint --test` sur les 192 fichiers .php modifiés depuis 336f312 (git diff --name-only --diff-filter=d) : PASS, 192 files.
- Migrations : `migrate:status` sans « Pending » ; 10 migrations ajoutées (8 Auth 2026_12_08_*, 2 Offer 2026_12_12_*), toutes avec `down()` ; aucune migration existante modifiée. Le `down()` de make_password_nullable redonne un mot de passe aléatoire avant de rendre la colonne obligatoire. Le `up()` n'a pas été rejoué à vide ni revenu en arrière (pas de `migrate:rollback`).
- Mobile : `npx tsc --noEmit` : aucune erreur. `npx jest --watchAll=false --forceExit` : « Test Suites: 74 passed, 74 total », « Tests: 470 passed, 470 total » (sortie dans /private/tmp/claude-502/-Users-bertrandzang-home-private-yamo-web/5868062b-0dc2-41b9-8f7d-40164951d546/scratchpad/qa-jest.log).
- Dépôt : `git log 336f312..HEAD` (web) et `5888a1b..HEAD` (mobile), noms de fichiers : aucun chemin database/migrations/migration-viewer, packages/media-manager ni .env ; seul `.env.example` est suivi côté mobile (antérieur). migration-viewer reste `??` (non suivi).
- Lecture du code et des tests (noms et assertions) pour chaque critère, détail ci-dessous.
Ne couvre pas : le rendu dans l'app (aucun simulateur : icônes, mise en page deux cartes par ligne, menu ⋮, panneau du cœur, textes affichés) ; seuls les tests Jest (composants simulés) et les tests d'API le couvrent. Pas d'envoi réel de SMS ou d'e-mail. Pas de relecture de sécurité complète. Pas de rejeu de `migrate:rollback`. Le contenu des fichiers de langue (de/en/fr) n'a pas été relu ligne à ligne. Pas de vérification sur un appareil ni sur Android.

Critères
1. Connexion sans mot de passe : OK.
   - LoginService::start (Modules/Auth/app/Services/LoginService.php:30-58) : compte trouvé, code envoyé ; inconnu, `next: register` avec l'identifiant (e-mail ou téléphone et pays). Tests AuthApiTest (« known email sends a code and no token », « unknown email leads to register with the email »). VerificationCodesApiTest:239 (register crée le compte après le code).
   - RegisterFormApiTest:30-37 : champs name, first_name, last_name, birth_date plus l'identifiant en champs cachés, `steps` vide ; l'envoi n'attend ni genre, ni mot de passe, ni description.
   - Genre : retiré de UserForm et AddressesRelationManager (back-office), `grep gender` dans Forms/Filament/Requests ne trouve rien. La colonne reste (voulu).
   - Mobile : SignInScreen.test.tsx:53 et :64 (connu, inconnu ; ni « Mot de passe oublié » ni « Créer un compte »). Les écrans de mot de passe oublié sont supprimés côté mobile ; le panneau Filament n'a pas `passwordReset()` (AdminPanelProvider:48).
2. Visiteur, onglets : OK (borné). GuestTabs.test.tsx:45 vérifie titre, invitation, « Se connecter ou s'inscrire » vers la connexion.
3. Liens du profil : OK. Tests ProfileMenuApiTest (sous-menus Informations personnelles et Confidentialité, ordre, icônes servies), IdentityApiTest (note, pays, type de pièce, fichiers, enregistrés), ResidenceApiTest, EmergencyContactApiTest, PrivacyApiTest (visibilité, lieu des avis, données JSON), AccountDeletionApiTest (raison obligatoire et code), NotificationSettingsApiTest (valeurs par défaut, enregistrement, canaux), UserPhonesApiTest (plusieurs numéros). Tous verts. Je n'ai pas relu les valeurs par défaut dans la migration `add_notification_settings_to_users` ; elles sont couvertes par « the form is filled with the defaults ».
4. Masquage et vérification : OK. ContactVerificationApiTest (profil masqué, code qui vérifie e-mail et téléphone, lien de vérification), LoginService:78-81 pose `phone_verified_at` après un code SMS, testé par VerificationCodesApiTest:94-103. Mobile : ProfileScreen.tsx:58,63 affiche `email_masked` et `phone_masked`, PartnerMembers.tsx:186-187 aussi (le lien tel:/mailto: garde la vraie valeur, voulu).
5. Favoris : OK. WishlistApiTest (panneau, rangement dans une liste choisie ou nouvelle, refus de « Récemment consultés », renommer et supprimer avec menu ⋮, remplissage à l'ouverture d'une fiche, limite 50, bloc d'accueil, deux colonnes sans menu avec croix). Mobile : OfferDetailScreen.tsx:51 appelle `/click` à l'ouverture, qui enregistre la vue (OfferCounterService:55) ; HomeScreen.test.tsx:151 vérifie l'ordre `home-recent` avant `home-featured` ; WishlistScreen.test.tsx:158-186 (menu ⋮, supprimer, deux cartes, croix) ; FavoriteHeart.test.tsx:59-80.
6. Tests, Pint, migrations, tsc, Jest : OK (chiffres ci-dessus).
7. Dépôt : OK (ci-dessus).

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Les formulaires `auth.password.forgot` et `auth.password.reset` restent enregistrés côté serveur (Modules/Auth/app/Providers/AuthServiceProvider.php:101-102) et servis par l'API : aucun écran de l'app ni du back-office ne les affiche, donc le critère 1 tient. Un client qui appelle directement `/forms/auth.password.forgot` les obtient encore. À supprimer ou à documenter si on veut le retrait complet.
- `UserResource` (Modules/Auth/app/Http/Resources/UserResource.php:20,31) renvoie toujours `gender` (et `email`/`phone` en clair pour le propriétaire) ; le type mobile le déclare encore (src/features/auth/types/index.ts:13). Aucun écran ne l'affiche, mais le champ est du bruit.
- Ma première commande Pint (liste de fichiers passée en un seul argument) a échoué sur la longueur, puis une seconde sur une mauvaise expansion ; la troisième (xargs) a donné PASS sur 192 fichiers. Aucune incidence sur le code.
