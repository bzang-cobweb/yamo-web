# Verdict qa-check — étape 1 (web : formulaires, langues, PATCH /auth/me)

- **Date** : 2026-11-15
- **Verdict** : GO — 0 bloquant, 0 majeur, 2 mineurs
- **Preuves rejouées par qa-check** : `php artisan test` 65/65 (211 assertions) ;
  `AuthApiTest.php` inchangé ; curl : `forms/auth.login` fr/en (email, password, pas de
  device_name), `forms/nope` 404, `forms/auth.profile.locale` sans jeton 401, `locales` 200,
  `PATCH auth/me` sans jeton 401 ; `scramble:export` identique, schéma Bearer déclaré ;
  balayage des routes au vert.
- **Preuves de la session** : mêmes routes en HTTP (DDEV) ; bug corrigé en cours d'étape —
  `config('app.locale')` est réécrite par `App::setLocale()` à chaque requête : la langue par
  défaut a désormais une source stable (`app.default_locale`, `Locales::default()`).

## Mineurs
- Règles dérivées par type : prouvées par `FieldTest` (cas valides, obligatoires, invalides par type), non relues une à une par qa-check.
- Sans `Accept: application/json`, une erreur renvoie la page de debug (APP_DEBUG local) — à vérifier en production.

## Ne couvre pas
Comportements avec jeton rejoués en direct (couverts par `FormsAndLocalesApiTest`) ; relecture
ligne à ligne des traductions ; le mobile.
