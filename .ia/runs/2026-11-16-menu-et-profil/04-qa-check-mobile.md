RAPPORT QA — 2026-11-16-menu-et-profil, étape 2 (mobile), tâche 04
Go : oui
Base du verdict : rejoué moi-même dans /Users/bertrandzang/home/private/yamo/mobile :
- npm test : 10 suites, 42 tests verts ; npm run typecheck : exit 0 ; npm run lint : exit 0.
- curl http://yamo.ddev.site/api/v1/messages en fr et en : les 24 clés de ApiMessageKey sont présentes et non vides dans les deux langues (0 manquante).
- Clés utilisées par t('...') (hors tests) : toutes dans MessageKey (seules offline.message et offline.retry sont hors ApiMessageKey, via OfflineKey, voulu). form.showPassword/hidePassword utilisées par ternaire (TextField). Aucun texte visible en dur repéré (grep JSX/title/label/Alert) hors OFFLINE_MESSAGES ; seule la flèche « ← » précède le texte servi profile.back.
- Traces : aucune occurrence de ProfileService.update, auth.profile.locale, UserSummary, ProfileLink dans src/README ; ProfileService ne garde que get/uploadAvatar/removeAvatar. Les 3 services (PersonalInfo PATCH /personal-info, Password PUT /password, Preferences PATCH /preferences) ont une méthode chacun, et correspondent aux routes réelles de Modules/Auth/routes/api.php.
- Lecture du code : Tabs de expo-router/js-tabs, icônes seules (tabBarShowLabel false, accessibilityLabel = t('tabs.*')), onglet index par défaut, profile en pile (menu visible sur les formulaires), sign-in hors (tabs) donc sans menu, écran hors ligne recouvre via ConnectivityGate. Profil : photo, nom, e-mail, 3 liens, déconnexion ; accueil : photo + salutation. Formulaires via FormLoader (auth.profile.personal/password/preferences) ; setUser pour informations et préférences ; succès → router.dismissTo('/profile', notice) → « profile.saved » / « profile.passwordSaved » ; erreur levée par onSubmit reste dans DynamicForm (422 sous les champs, constat orchestrateur). README à jour (services, navigation, routes, régénération des routes typées).
Ne couvre pas : rendu réel sur simulateur/appareil (pris tel que constaté par l'orchestrateur : menu, profil, formulaire mot de passe, 422) ; enregistrement réussi depuis l'app, retour au profil + confirmation, setUser après succès, bascule de langue après préférences (jamais joués ; aucun test automatisé ne couvre le chemin de succès de ProfileFormScreen) ; exécution Android/web ; textes de l'API au-delà de la présence des clés.

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Chemin de succès des formulaires (dismissTo + message de confirmation, setUser) non rejoué ni testé automatiquement ; ProfileScreen.test (2 tests) et profile-services.test (3 tests) ne l'exercent pas. À jouer sur un compte de test.
- src/shared/api/__tests__/ApiClient.test.ts:24-27 utilise encore l'exemple client.patch('/v1/auth/me', { locale: 'en' }) : simple test générique du client, mais reste une trace littérale de l'ancien appel ; remplacer l'exemple pour que la recherche du critère 4 soit propre.
- Fichiers de la tâche encore non commités (RM index/profile déplacés, suppressions stagées) : à commiter par l'orchestrateur.
