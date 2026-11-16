RAPPORT QA — 2026-11-18-etapes-mot-de-passe-oublie
Go : oui
Base du verdict : rejoué moi-même : web `ddev artisan test` 179 passed (848 assertions) ; Pint --test PASS 252 fichiers ; migrate:status sans migration en attente ; `ddev composer openapi` puis diff avec l'ancien openapi.json : identique, sans erreur ; mobile jest 18 suites / 76 tests verts, tsc --noEmit sans erreur, eslint src sans erreur ni avertissement. Lecture du code pour les critères 2-9 (config auth.verification_codes + .env.example AUTH_CODE_* commentées + test de changement de valeur VerificationCodesApiTest:134-138 ; plus de constante ni de `$codes` ; RegionsRelationManager + RegionResource supprimée + test ; BackHeader hors ScrollView, libellés « Profil »/« Se connecter », « Étape précédente » dans ChallengeFlow ; genre à l'étape 1 après le nom ; Step/FormStepController/route steps ; PasswordResetService ; clés fr+en pour auth.forgotPassword/passwordReset/previousStep/signIn et form.next/previous/stepOf ; RouteGuardTest couvre password/forgot et password/reset ; pas de secret dans les diffs, seul « secret-password » dans un test).
Ne couvre pas : exécution réelle sur appareil/simulateur (rendu visuel du en-tête fixé, parcours manuel), non-régression visuelle Filament dans un navigateur, rejeu de chaque test individuel hors suite, temps de réponse (timing) de « identifiant inconnu » vs connu, endpoint steps en 404/422 relu seulement via les tests, lecture de .env.

Bloquants : 0

Majeurs : 0

Mineurs : 1
- Critère 5 : le libellé « Profil » du retour dépend de la clé profile.title via ProfileFormScreen (mobile/src/features/auth/screens/ProfileFormScreen.tsx:32) ; EmailChangeScreen et PhoneChangeScreen passent par ProfileScreenShell : je n'ai pas vérifié visuellement qu'ils affichent le même en-tête (non rejoué sur appareil).
