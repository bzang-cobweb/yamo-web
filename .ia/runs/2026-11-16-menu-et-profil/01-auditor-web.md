RAPPORT — 2026-11-16-menu-et-profil, tâche 01 : audit web

Livrable      : .ia/memory/spec_profil_formulaires_web.json, un JSON valide (`jq empty` OK)
Preuve        : chaque affirmation de la spec cite fichier:ligne. Pour rejouer les relevés : `grep -rn` des noms listés dans `references_a_supprimer_ou_mettre_a_jour.methode`
Périmètre     : écrit : la spec et ce rapport. Code lu seulement, rien commité (consigne)
Déplacements  : aucun ; dossier .ia/runs/2026-11-16-menu-et-profil/ créé
Non couvert   : voir plus bas
Effets de bord: aucun
Règle générale: « les règles d'une FormRequest sont lues sur son formulaire ». Écrit dans Form.php:8-13, .ia/context/project.md (module Form) et UpdateMeRequest.php:27-43, qui est le modèle à reprendre. Garde des routes : RouteGuardTest.php et api.php:9-10
Reste ouvert  : les incertitudes de la spec

## Ce qui sert à l'implémentation
- **Routes** : Modules/Auth/routes/api.php:17-23, groupe `auth:sanctum`. On supprime :20. On ajoute trois routes `->can('updateMe', User::class)`. RouteGuardTest.php:27-40 les accepte sans nouvelle exception.
- **Requêtes** : reprendre l'`authorize` d'UpdateMeRequest.php:19-22, et `new XForm($this->user())` (:40-43) pour les règles et les attributs.
- **Formulaires** : Field.php:35-93 fournit les fabriques. `password` donne la règle `string` (FieldType.php:43). `help` est à Field.php:109 et `default` à :123. Inscription au registre : AuthServiceProvider.php:34-36.
- **current_password** : sans paramètre, la règle vise le garde par défaut (ValidatesAttributes.php:611). Sur l'API, ce garde est sanctum par UseSanctumGuard.php:20 (bootstrap/app.php:20). Seul `web` est déclaré dans config/auth.php:40-45.
- **Révoquer les autres jetons** : `currentAccessToken()` (HasApiTokens.php:94-97). LogoutService.php:19-23 en montre l'usage, avec le test `instanceof PersonalAccessToken`.
- **Mot de passe** : le cast `hashed` est à User.php:54. `Password::defaults()` n'est configuré nulle part, donc min(8) (Password.php:166-173).
- **Messages fr** : confirmed :29, current_password :31, different :38, password.* :116-121 (lang/fr/validation.php).

## Références à supprimer (relevé complet dans la spec)
- Code :
  - api.php:20
  - ProfileController.php:7 et 25-35
  - ProfileService.php:15-26
  - UpdateMeRequest.php (fichier entier)
  - ProfileLocaleForm.php (fichier entier)
  - AuthServiceProvider.php:13 et 36
  - docblock UserPolicy.php:56
  - `locale_help` dans lang/{fr,en}/form.php:11
- Tests : FormsAndLocalesApiTest.php, aux lignes 11-15, 54-63, 65-70, 86-89, 91-109, 121-132, 134-144, 146-162 et 164-167.
- Contrat et documentation :
  - openapi.json:281-326, 292 et 496-512 (à régénérer avec `ddev composer openapi`, composer.json:83-84)
  - .ia/context/project.md:50-52 et 63-64

## Pièges principaux
1. Si on supprime le PATCH en gardant le GET `me`, un `PATCH /api/v1/auth/me` répondra 405 et non 404. C'est déduit du routage, pas vérifié.
2. `Field::rules()` remplace la liste des règles au lieu de l'allonger (Field.php:146-151).
3. La règle `confirmed` attend un champ `<champ>_confirmation`.
4. `current_password` échoue hors du groupe api, où le garde par défaut est `web`.
5. Un TransientToken n'a pas de clé (Guard.php:32-37), et `Sanctum::actingAs` pose un mock de jeton (Sanctum.php:72-82). Les tests de révocation doivent donc utiliser de vrais jetons et appeler `forgetGuards()` (AuthApiTest.php:142-158).
6. Aucune clé de traduction n'existe pour « ancien », « nouveau » et « confirmation » du mot de passe.
7. Les textes mobiles doivent avoir les mêmes clés en fr et en en, sans doublon (MobileMessages.php:39-41, MessagesApiTest.php:49-59).
8. SetLocale s'exécute avant l'action : la réponse à un changement de langue arrive dans l'ancienne langue.

## Ce que l'audit ne couvre pas
- Les skills bza-dev:audit-source et bza-laravel:audit-laravel sont introuvables localement. L'enveloppe de la spec reprend les clés des specs existantes, sans garantie de conformité.
- Non exécutés : le code 405, l'export Scramble avec la règle `Password` et les messages exacts de la règle `Password`.
- Non lus : la définition de la capacité `viewForm`, le reste de Filament et le côté mobile.
- Méthode de relevé : grep sur les noms (classes, clé de formulaire, nom de route, `patchJson`, `auth/me`, `locale_help`). Une référence construite dynamiquement (chaîne concaténée) lui échapperait.
