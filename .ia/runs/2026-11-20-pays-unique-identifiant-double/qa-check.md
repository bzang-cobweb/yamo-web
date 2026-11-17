RAPPORT QA — 2026-11-20-pays-unique-identifiant-double
Go : oui
Base du verdict : rejoué moi-même.
- `ddev artisan test` : 231 passés, 1149 assertions, 0 échec.
- `ddev exec vendor/bin/pint --test` : propre (281 fichiers, PASS).
- `npx jest --forceExit` : 27 suites, 98 tests passés. `npx tsc --noEmit` : 0 erreur. `npx expo lint` : code retour 0, aucune sortie d'erreur.
- OpenAPI : `scramble:export --path=/tmp/openapi-check.json` puis `diff` avec openapi.json : aucune différence.
- Aucune dépendance : composer.json, composer.lock, package.json et package-lock.json sont absents de `git status` dans les deux dépôts.
- Test jetable hors dépôt (copié dans /tmp du conteneur, base SQLite en mémoire, `config()` pour les canaux et les pays) : 3 tests, 24 assertions, tous verts.
  - Deux canaux, `channel=email` avec `phone=zzz` et `phone_country=XX` : accepté (champs masqués ni validés ni gardés).
  - Mauvais mot de passe : l'erreur est sur `email` (canal e-mail) ou sur `phone` (canal téléphone), jamais sur l'autre champ.
  - `channel` absent : 422. `channel=phone` sans numéro : erreurs sur `phone` et `phone_country`. Ancien `identifier` : 422.
  - Mot de passe oublié : accepté dans les deux canaux, champ masqué ignoré.
  - Pays unique CH : `phone_country=FR` refusé à la connexion et au changement de numéro. `country_id=999` refusé aux informations personnelles. `country_id` envoyé : enregistré. Changement de numéro avec CH : 202.
  - Avec un second pays actif : `locked` repasse à faux.
  - Limite de débit : 5 essais pour un même téléphone, le sixième renvoie 429. Un autre numéro n'est pas limité (422).
- Lecture du code : `Field::isLocked()` (select ou phone, sans `dependsOn`, une seule option) et `toArray()` (`locked`, `default`, `visible_when`). La région dépendante n'est jamais verrouillée (test `region_id locked=false`). `SingleCountry` pour le back-office, utilisé sur `country_id` et `phone_country_id`. Côté app, `locked` est traité de façon générique : liste désactivée mais envoyée, téléphone dont seul le pays est désactivé, champ masqué non envoyé (`submittedValues`). Test jest `FieldConditions.test.tsx` vert.
- Back-office : couvert par `test_the_back_office_locks_a_single_active_country_and_saves_it` (champs désactivés, pré-remplis, enregistrés), rejoué dans la suite complète.
Ne couvre pas : le rendu visuel réel de l'app (simulateur, appareil) ; le rendu navigateur du back-office avec plusieurs pays actifs (le comportement « rien ne change » repose sur le test de l'API et l'absence de changement de code sur ce chemin) ; la base de développement ; le comportement des SMS et e-mails réels.

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Une référence à `identifier` reste dans le code de connexion, au sens littéral du critère 4 : le trait `Modules/Auth/app/Forms/Concerns/HasIdentifierField.php`, la méthode `identifierFields()`, la clé de traduction `auth::user.identifier` (« E-mail ou téléphone ») qui sert d'étiquette au sélecteur `channel`, et la classe `Identifiers`. Aucun champ, paramètre d'API ni code de l'app hors tests ne l'utilise. Le grep sur `src` de l'app ne trouve `identifier` que dans les tests, et ceux-ci passent encore `{ identifier: ... }` à des mocks sans effet.
- Des tests mobiles (`challenge-services.test.ts`, `ForgotPasswordScreen.test.tsx`, `ChallengeFlow.test.tsx`) gardent `identifier` comme donnée d'exemple. Ce n'est pas du code de l'app, mais cela contredit le contrat d'API actuel.
- Le dossier `Modules/Location/app/Filament/Forms/` (`SingleCountry`) n'est pas suivi par git (`??`). Il doit être ajouté au commit, sinon le back-office casse après un clone.
