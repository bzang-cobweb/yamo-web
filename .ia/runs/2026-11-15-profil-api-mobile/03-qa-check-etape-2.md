# Verdict qa-check — étape 2 (connexion API Sanctum)

- **Date** : 2026-11-15
- **Verdict** : GO — 0 bloquant, 0 majeur, 2 mineurs
- **Preuves rejouées par qa-check** : `php artisan test` 49/49 (172 assertions) ;
  `route:list` : login, logout, me (+ docs Scramble), `GET /api/user` → 404 ;
  `scramble:export` régénère un `openapi.json` identique ; curl sur yamo.ddev.site :
  5 × 422 puis 429, `/me` et `/logout` sans jeton → 401, validation traduite fr/en.
- **Preuve de la session** : parcours réel avec un compte de test — login 200 + jeton,
  `me` 200, logout 204, `me` avec le jeton révoqué 401, erreur d'identifiants en français.

## Mineurs
- « device name » non traduit dans les messages de validation → **corrigé** après le
  verdict (`LoginRequest::attributes()`), constaté en HTTP fr/en.
- Le 401 « Unauthenticated. » reste en anglais en fr → **non traité** (traduction Laravel).

## Ne couvre pas
Throttle derrière un proxy / en production (clé e-mail + IP) ; stockage du jeton côté
mobile (étape 3).
