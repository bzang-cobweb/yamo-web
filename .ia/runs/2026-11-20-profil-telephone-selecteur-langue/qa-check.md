RAPPORT QA — 2026-11-20-profil-telephone-selecteur-langue
Go : non (NO-GO)
Base du verdict : rejoué moi-même.
- `ddev artisan test` : 202 passés (953 assertions), 0 échec.
- `ddev exec vendor/bin/pint --test` : propre (270 fichiers).
- `ddev composer openapi` puis `git diff openapi.json` : +8 lignes (`phone_international` : propriété et entrée `required`). openapi.json n'était PAS modifié dans l'arbre avant mon rejeu (absent de `git status` initial). Je l'ai restauré à l'état d'origine (`git checkout openapi.json`) après constat.
- Mobile : `npx jest --forceExit` 25 suites, 91 tests verts ; `npx tsc --noEmit` code 0 ; `npx expo lint` code 0.
- package.json, package-lock.json (mobile), composer.json, composer.lock (web) : aucun diff, aucune dépendance ajoutée (expo-symbols et OptionsModal déjà présents).
- Lecture du diff : critères 1 à 4 conformes dans le code (UserResource + PhoneNumbers::international ; titre retiré ; LocaleSwitcher via OptionsModal au-dessus du logo, masqué si une langue ; formulaire GET /locale?locale= avec select, noscript, 404 si inconnue ; headerShown false sur Accueil, `!inSubPage` sur Profil, Stack du profil sans header).

Ne couvre pas : rendu visuel réel (simulateur, navigateur), absence de JavaScript sur le web testée seulement par lecture du HTML, centrage visuel au-dessus du logo, TranslatableTabs.php (hors périmètre).

Bloquants : 1
- Critère 1 : `phone_international` n'est pas documenté dans openapi.json livré. `ddev composer openapi` produit un diff de 8 lignes (propriété + `required`) que le livrable ne contient pas. Reproduire : `ddev composer openapi && git diff openapi.json`. Correction : régénérer et inclure openapi.json.

Majeurs : 0

Mineurs : 1
- Le commentaire de `(tabs)/_layout.tsx` (« une sous-page du profil, qui a son propre en-tête ») reste ambigu avec « sous-pages sans barre du haut » ; sans effet sur le comportement.

---

## Deuxième passage

RAPPORT QA — 2026-11-20-profil-telephone-selecteur-langue (deuxième passage)
Go : oui (GO)
Base du verdict : rejoué moi-même, sans toucher aux fichiers suivis.
- `ddev exec php artisan scramble:export --path=/tmp/openapi-check.json` puis `ddev exec diff /tmp/openapi-check.json openapi.json` : identiques. `git diff openapi.json` : +8 lignes, `phone_international` (propriété et `required`) présent dans le livrable. Le bloquant du premier passage est levé.
- `ddev artisan test` : 202 passés (953 assertions). `ddev exec vendor/bin/pint --test` : propre (270 fichiers).
- Mobile : `npx jest --forceExit` 25 suites, 91 tests verts ; `npx tsc --noEmit` code 0 ; `npx expo lint` code 0.
- composer.json, composer.lock, package.json, package-lock.json : aucun diff, aucune dépendance ajoutée.
- Périmètre du diff inchangé par rapport au premier passage dont la lecture avait validé les critères 1 à 4 ; TranslatableTabs.php exclu.

Ne couvre pas : rendu visuel réel (simulateur, navigateur), centrage visuel au-dessus du logo, fonctionnement sans JavaScript vérifié seulement par lecture du HTML et tests, TranslatableTabs.php (hors périmètre). Je n'ai pas relu le diff de code en détail lors de ce passage, je m'appuie sur la lecture du premier passage et sur les tests rejoués.

Bloquants : 0
Majeurs : 0
Mineurs : 1
- Commentaire de `(tabs)/_layout.tsx` ambigu (signalé au premier passage), sans effet sur le comportement.
