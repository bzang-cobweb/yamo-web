# 01 — auditor web — genre, date de naissance, initiales

STATUT : TERMINÉ (non commité, comme demandé)

## Spec produite
- `.ia/memory/spec_genre_date_web.json` (JSON valide, vérifié par `python3 -m json.tool`)

## Constats clés (source fichier:ligne)
- `FieldType::rules()` est un `match` exhaustif sans `default` (FieldType.php:42-49). Un case `DATE` sans bras lève donc `UnhandledMatchError`. RADIO ajoute déjà `string` + `Rule::in(options)` (l.46).
- `Field::default()` est renvoyé tel quel (Field.php:193). Les valeurs d'options sont converties en string (l.161, 195). `rules()` remplace la liste des règles au lieu de l'allonger (l.146-151). `required` existe déjà (l.20, 116-121, 162, 192).
- `User` : `#[Fillable]` sur l.28, `casts()` l.50-56, `fullName` l.61-64. `initials` n'existe pas.
- Enums : `PermissionEnum` n'a que `label()` (l.25-28), `RoleEnum` n'a aucune méthode. Aucun `options()` dans les enums. Le seul modèle d'`options()` est `Locales::options()` (app/Support/Locales.php:37-42).
- `UserResource` a 7 clés, et le PHPDoc l.15 est lu par Scramble (openapi.json:636-673).
- `PersonalInfoService` lit `$data['first_name']` et `$data['last_name']` directement (l.17-20).
- La migration doit être datée après `2026_11_15_170000`. Le module charge ses migrations via nwidart (ModuleServiceProvider.php:63-64).
- Config : timezone `UTC` (config/app.php:68), locale `fr` (l.81). Tests sous SQLite `:memory:` (phpunit.xml:30-31).

## Pièges majeurs
1. Un Carbon passé à `json_encode` sort au format `"2000-01-02T00:00:00.000000Z"` (vérifié). Il faut formater en `Y-m-d` dans `UserResource` et dans `Field::default`.
2. Le cast `date` stocke `Y-m-d H:i:s` (HasAttributes.php:1638-1643). Sous SQLite, un `assertDatabaseHas('2000-01-02')` échoue donc.
3. Dans les messages `before:today` et `after:1900-01-01`, `:date` est remplacé par la valeur brute. Le message dira « antérieure au today », car il n'y a pas de clé `validation.values` (ReplacesAttributes.php:818-825, FormatsMessages.php:506-530).
4. `before` et `after` sont stricts. Or le sélecteur mobile est « limité à aujourd'hui » : la date du jour serait proposée par l'app puis refusée par le serveur.
5. Ajouter `Rule::enum` en plus du `Rule::in` déjà présent sur RADIO ferait double emploi.
6. `Str::ascii` retire bien les accents (Élodie→Elodie, Ängel→Angel, Øster→Oster), mais avec la langue `de`, Ängel devient Aengel.

## Tests à mettre à jour
- AuthApiTest.php:40-51 (`assertExactJson` de UserResource).
- ProfileFormsApiTest.php:41 (2 champs) et :48-56 (PATCH sans genre, `assertOk`).
- FieldTest.php:65-70 (couverture de tous les types).
- Filament UserResourceTest.php:75-86 (création sans genre) et :162-165 (édition d'un utilisateur de la factory sans genre).
- MessagesApiTest.php:49-60 : un texte « Effacer » doit exister en fr et en en.

## Ce que l'audit ne couvre pas
- Le skill d'enveloppe `bza-dev:audit-source` n'est pas disponible. La structure reprend les clés des specs existantes.
- Non lus : UserTable, CreateUserCommandTest, AvatarApiTest, UserAvatarTest, le reste de `lang/*/validation.php`, le côté mobile.
- Non vérifiés : la présence de ConvertEmptyStringsToNull, le rendu Scramble d'un enum ou d'une date nullable, l'usage de `HasLabel` par Filament pour un enum.
- Non tranchés par la source : `before` ou `before_or_equal:today` ; une `birth_date` absente de la requête signifie-t-elle « inchangée » ou « effacée » ?

## Fichiers lus
La liste complète est dans `fichiers_lus` de la spec. `.env` n'a pas été lu.
