RAPPORT QA — 2026-11-20-accueil-nom-langue
Go : oui
Base du verdict : rejeu moi-même. Mobile : jest 25 suites / 91 tests verts, tsc exit 0, expo lint exit 0. Web : ddev artisan test 200 passed (944 assertions), pint --test PASS (270 fichiers). package.json, composer.json et composer.lock sans diff (git status). Lecture du diff des deux dépôts et des fichiers neufs (LocaleSwitcher, locale-storage, device-storage, SetSessionLocale, routes/web.php, RegisterService, SetLocale, ApiClient).
Ne couvre pas : rendu réel sur appareil/simulateur (encoche, barre du haut, redémarrage de l'app) ; aucun test bout en bout inscription mobile -> API ; commits e1083a1 et 2c02f05 (hors périmètre).

Critères
1. Accueil : avatar puis nom complet : OK. HomeScreen rend Avatar puis ThemedText full_name ; test HomeScreen.test.tsx vert.
2. Barre du logo : OK par lecture, non vu à l'écran. (tabs)/_layout.tsx : headerShown: !useSegments().length>2 sur Profil, donc masquée sur profile/<formulaire>, visible sur les racines ; ProfileScreenShell passe à edges ['top','left','right'] (zone sûre). Aucun test ne couvre ce comportement.
3. Sélecteur invité : OK. LocaleSwitcher (GET locales via localeService, courante marquée par accessibilityState selected, appel chooseLocale au toucher), monté dans WelcomeScreen ; la langue est dérivée de l'état, donc sans rechargement ; 3 tests verts + test AuthProvider du changement.
4. Mémorisation : OK. locale-storage via device-storage (expo-secure-store existant), relue au démarrage (localeKnown attend chosenLocale), préférée à l'appareil ; `user?.locale ?? guestLocale` : le compte prime. Tests AuthProvider.locale vert (relecture 'en' avant 'fr-CH').
5. Inscription : OK par lecture. apiClient.setLocale(locale) à chaque rendu -> en-tête Accept-Language (ApiClient.ts:69) ; SetLocale est sur le groupe api (bootstrap/app.php:21) ; RegisterService.php:46 écrit 'locale' => App::getLocale().
6. Web / : OK. SetSessionLocale (session, sinon getPreferredLanguage, sinon défaut), liens avec aria-current, route /locale/{locale} avec whereIn -> 404 pour inconnue ; 4 tests ApplicationTest verts.
7. Tests, outils, dépendances : OK (chiffres ci-dessus).

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Critère 5 : aucun test ne fixe la chaîne « Accept-Language en -> compte créé avec locale en » (aucun assert sur locale dans Modules/Auth/tests/Feature/Api/RegisterStepsApiTest.php) ; le chemin n'est établi que par lecture du code.
- Critère 2 : aucun test sur la visibilité de la barre ni sur la zone sûre (useSegments().length > 2 repose sur la profondeur des segments d'Expo Router, à confirmer sur appareil).
- web/app/Filament/Schemas/TranslatableTabs.php : ->contained(false) devient ->contained(true), changement non mentionné dans le contrat ni dans la décision ; à confirmer comme voulu, hors critères.
