RAPPORT QA — 2026-11-17-formulaires-profil-generiques, tâche 01
Go : oui
Base du verdict : rejeu moi-même (aucun fichier modifié hors ce rapport)
- web : `ddev artisan test --compact` = 263 passed (1296 assertions) ; `pint --test` = PASS 298 fichiers ; composer.json/composer.lock inchangés ; openapi.json dans le diff (menu `path` -> `form`, description de `submit`).
- mobile : `npx jest` = 27 suites, 119 tests passed ; `tsc --noEmit` exit 0 ; `npm run lint` exit 0 ; package.json/package-lock.json inchangés.
- Code lu : menu (ProfileMenuKey/Service/Controller), Submission::toRoute (méthode et chemin lus sur la route nommée), 6 formulaires avec submission(), FormResource (`submit` = label + submission), ProfileFormScreen, ProfileFormService, route `[form].tsx`.
- Tests web lus : ProfileFormSubmissionApiTest couvre les 6 formulaires (method/endpoint/destructive/success), auth.login garde `submit = {label}`, chaque endpoint servi correspond à une route `api.v1.*`, l'envoi servi enregistre (langue), et un formulaire + route nouveaux (test.nickname) sont servis sans autre changement.
- Tests mobile lus : ProfileFormScreen.test avec un formulaire inconnu (`notifications.settings`) : titre, envoi à l'endpoint décrit, compte mis à jour, code puis compte, sign-out, refus 422. profile-services.test : profileSubmission refuse adresse absolue, `//`, hors /v1, `..`, `?`, méthode GET, action inconnue, sans message, sans envoi.
- Dossier app : `src/app/(tabs)/profile/` ne contient plus que `[form].tsx`, `_layout.tsx`, `index.tsx` ; 6 écrans, 6 routes/services supprimés (indexés). grep dans src : seul nom de formulaire du profil hors tests = `auth.profile.delete-account` (DeleteAccountLink). Aucun e2e dans le dépôt mobile.
- Sécurité : regex d'endpoint `^/v1(/[A-Za-z0-9_-]+)+$` (pas de `%`, `.`, `?`, `:` : pas de contournement vu) ; méthode et action en liste blanche ; le jeton n'est envoyé que via apiClient (baseUrl fixe). Message de succès gardé en mémoire, pas dans l'URL.
Ne couvre pas : exécution sur appareil/simulateur (aucun parcours réel iOS/Android) ; le rendu visuel (bouton rouge `destructive`, en-tête) ; le lancement de `composer openapi` (interdit) : je n'ai pas vérifié que openapi.json est bit-à-bit celui que la commande produirait, seulement qu'il reflète le changement ; les tests de bout en bout contre un serveur réel.

Bloquants : 0

Majeurs : 0

Mineurs : 4
- Références mortes dans la doc mobile : `/Users/bertrandzang/home/private/yamo/mobile/README.md` ligne 70 (cite PersonalInfoService, PasswordService, PreferencesService) et lignes 96-97 (routes `/profile/personal`, `/profile/password`, `/profile/preferences`) ; commentaire de `src/features/auth/services/ProfileService.ts` lignes 8-9 (mêmes services supprimés).
- `destructive` est servi et typé, mais je n'ai pas vu de test mobile qui vérifie que le bouton devient rouge (grep non concluant dans les tests lus) ; non couvert par le critère strict.
- « Un formulaire sans envoi exécutable affiche une erreur sans rien envoyer » : prouvé au niveau unitaire (profileSubmission = null) et le rendu `profile-form-unsupported` existe dans l'écran, mais aucun test d'écran ne l'exerce.
- Contrôle d'endpoint : l'app accepte tout `/v1/<segments>` fourni par le serveur (ex. un endpoint `/v1/auth/logout` ou une route sans rapport) ; cohérent avec la décision (le serveur est la source de confiance), mais le test web ne vérifie que le préfixe `api.v1.` des routes des 6 formulaires, pas une liste blanche d'API.
