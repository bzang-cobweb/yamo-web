# 01 — auditor web — pays, téléphone, codes

STATUT : TERMINÉ (non commité, comme demandé)

## Spec produite
- `.ia/memory/spec_pays_codes_web.json` : le JSON est valide (`python3 -m json.tool`).

## Constats clés (fichier:ligne)
- **Créer un module** : `php artisan module:make Location`, qui lance lui-même `composer dump-autoload` (ModuleMakeCommand.php:82). Le merge-plugin inclut déjà `Modules/*/composer.json`. `modules_statuses.json` est tenu par le FileActivator (config/modules.php:328-335). Les stubs viennent du vendor (l.37-39). Les dossiers `lang`, `Models`, `Policies` et `Filament` se créent à la main (l.157-216).
- **Filament** : le panel découvre `Modules/<M>/app/Filament/{Resources,Pages,Widgets}` de chaque module actif (AdminPanelProvider.php:35-41). Une ressource étend `AbstractResource` (actions, AbstractResource.php:22-42). Elle passe par une base de module qui ne définit que le groupe de navigation (AbstractAuthResource.php:12-15).
- **Permissions** : `AuthDatabaseSeeder` supprime toute permission absente de `PermissionEnum` (l.26-28). Les permissions de Location vont donc dans `Modules/Auth/app/Enums/PermissionEnum.php`, avec leurs libellés sous `auth::permission.names.<domaine>.<action>`. Les policies sont enregistrées par `Gate::policy` dans le provider du module (AuthServiceProvider.php:31-33).
- **Translatable** : aucun modèle n'est traduisible aujourd'hui. Le plugin lara-zeus est branché avec `defaultLocales(['fr','en'])` (AdminPanelProvider.php:48-51). Pour une ressource, il faut le trait `Resources\Concerns\Translatable`, le trait de chaque page et l'action `LocaleSwitcher` (vendor lara-zeus src/…).
- **Form** : `Field` avec `options` valeur ⇒ libellé, `toArray` à 8 clés (Field.php:189-204). `rules()` remplace les règles au lieu de les ajouter (l.151-156). `FieldType::rules()` est un match exhaustif (FieldType.php:42-53). `Form::rules()` et `attributes()` parcourent tous les champs (Form.php:75-91) : c'est là qu'il faut exclure les champs `readonly`.
- **Auth** : `#[Fillable]` est en User.php:30 et `casts` en l.52-60. L'e-mail est unique et non nullable (create_users_table.php:17). Il est `required` dans UserForm.php:73 et dans CreateUserCommand.php:39 et :48. Le genre est en `Radio` (UserForm.php:57-61) et en `Field::radio` (PersonalInfoForm.php:40-44). `LoginService` valide `['email','password']` par la garde web (l.25) ; le limiteur se base sur `input('email')` (AuthServiceProvider.php:43-44).
- **Mail, queue, logs (config résolue en DDEV, sans lire .env)** : mail smtp 127.0.0.1:1025 (Mailpit) ; queue `database` sans worker ; logs `stack` → `single` (laravel.log). Il n'existe aucun Mailable ni aucune Notification.
- **laravel-phone** : la règle `phone` lit par défaut `<attribut>_country` (Rules/Phone.php:170), qui doit contenir un code ISO-2. Le cast E164 cherche `<clé>_country` (PhoneNumberCast.php:19-32). libphonenumber expose `getSupportedRegions()` (PhoneNumberUtil.php:621), soit 245 régions, et `getCountryCodeForRegion()` (l.2276), avec CH → 41.
- **Middleware** : `bootstrap/app.php:20`, `api(append: [UseSanctumGuard, SetLocale])`. SetLocale lit déjà `$request->user('sanctum')` (SetLocale.php:20).

## Pièges majeurs
1. **`Locale::addLikelySubtags` n'existe pas** en PHP 8.4.20 (DDEV) ni en 8.4.1 (hôte) : vérifié, l'appel lève « undefined method ». ICU 76 n'expose pas de table de likely subtags exploitable par ResourceBundle. Le nom natif demande donc une table pays → langue. `getDisplayRegion` fonctionne (Deutschland, 日本, Schweiz).
2. La clé de traduction `validation.phone` est **absente** en fr et en en. Sans elle, le message affiché est la clé brute (critère 5).
3. La règle `phone` exige un code ISO dans `phone_country`. Si l'app envoie un id de Country, il faut le convertir avant la validation.
4. Le LocaleSwitcher Filament ne connaît que fr et en : la clé de stockage du nom natif n'est pas tranchée par le contrat.
5. SELECT et RADIO imposent `string` + `Rule::in` : un id envoyé en entier JSON échoue. `Rule::in` ne vérifie pas non plus l'appartenance d'une région à son pays.
6. Le jeton d'étape : s'il est Sanctum, il est accepté partout, car aucune route ne contrôle les abilities et Sanctum n'a pas d'expiration (sanctum.php:53).
7. La queue `database` tourne sans worker : un mail mis en file ne part pas en local. Une adresse nouvelle n'a pas de `preferredLocale` : il faut fixer la langue de l'envoi.
8. `last_seen_at` : `update()` touche aussi `updated_at`. Le middleware tourne aussi sur les routes publiques.
9. Migrations : celle de users doit suivre countries et regions (clés étrangères). Il faut horodater après `2026_11_16_120000`.

## Tests touchés
- Login en deux étapes : AuthApiTest.php:24-32 (helper `login()` lisant `token`), utilisé l.34-66, 122-165 ; l.68-95 (erreurs sur `email`) ; l.110-120 (throttle). FormsAndLocalesApiTest.php:25-52 (assertExactJson de `auth.login`) et l.72-79.
- E-mail nullable : UserResourceTest.php:127-141 (`email` required) ; CreateUserCommandTest.php:23-89 ; AuthApiTest.php:43-58 (assertExactJson de UserResource, 11 clés).
- Genre en select : ProfileFormsApiTest.php:37 (liste exacte des champs) et :41 (`radio`) ; l.51-72 (« four fields »).
- Form : FieldTest.php:66-71 (couverture des types) et :107-121 (toArray exact).
- RouteGuardTest.php:16-21 : les nouvelles routes publiques sont à déclarer. MessagesApiTest.php:49-59 : mêmes clés en fr et en.

## Ce que l'audit ne couvre pas
- Les skills `bza-dev:audit-source` et `bza-laravel:audit-laravel` ne sont pas accessibles : l'enveloppe est libre. Les specs existantes n'ont pas été lues (audit frais).
- Non lus en entier : UserResourceTest, CreateUserCommandTest, ProfileFormsApiTest (au-delà des lignes 28-73), AvatarApiTest, PolicyOutsideFilamentTest, PasswordForm, PreferencesForm et leurs services, RoleForm, RoleTable, PermissionResource, lang en/*, TranslatedApiErrors (après la ligne 34).
- Non vérifiés : le comportement de Filament v5 sans policy ; le remplacement Radio → Select avec `fillForm` ; un envoi réel vers Mailpit.
- Le côté mobile n'est pas couvert.

## Fichiers lus
Liste complète dans `fichiers_lus` de la spec. `.env` et `.env.example` n'ont pas été lus.
