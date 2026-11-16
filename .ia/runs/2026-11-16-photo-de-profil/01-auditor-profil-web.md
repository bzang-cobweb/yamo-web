# Tâche 01 — Audit du profil utilisateur (web)

STATUT : TERMINÉ (avec réserves, voir « Non couvert »)

## Spec produite
- `.ia/memory/spec_profil_utilisateur_web.json` : JSON valide (vérifié avec `json_decode` et `JSON_THROW_ON_ERROR`). Pas commitée, comme demandé.

## Pour implémenter
- **Collection** : `User` n'implémente ni `HasMedia` ni `HasAvatar` (User.php:25). Ajouter `InteractsWithMedia` aux traits (User.php:28). `booted()` existe déjà (User.php:34-37, révocation des jetons). Aucun modèle du dépôt n'utilise encore medialibrary.
- **Routes** : dans le groupe `auth:sanctum` (Modules/Auth/routes/api.php:17-21), avec `->can('updateMe', User::class)` comme le PATCH (api.php:20). Sans `can:`, `RouteGuardTest` échoue (RouteGuardTest.php:16-20,33-41).
- **Requête** : `UpdateMeRequest` tire ses règles de `ProfileLocaleForm` (UpdateMeRequest.php:27-43). Il faut donc une FormRequest dédiée. Reprendre son `authorize` (UpdateMeRequest.php:19-22).
- **Service** : `ProfileService` ne contient que `update()` (ProfileService.php:18-23).
- **Policy** : `updateMe` vaut `update($user, $user)`, et hors Filament la règle est `$model->is($user)` (UserPolicy.php:59-62,74-82). Le super administrateur passe par `before` (HasBeforeAbility.php:13-16).
- **UserResource** : 6 champs (UserResource.php:19-26). Le PHPDoc de forme (ligne 15) alimente Scramble. `toArray` reçoit `$request` (ligne 17).
- **Filament** :
  - Formulaire : `UserForm::getGeneralSection` (UserForm.php:27-68).
  - Liste : `UserTable` columns (UserTable.php:17).
  - Les composants `SpatieMediaLibraryFileUpload` et `SpatieMediaLibraryImageColumn` sont présents dans le vendor.
  - Barre du haut : Filament appelle `getFilamentAvatarUrl()` si le modèle implémente `HasAvatar`, sinon il lit l'attribut `avatar_url` (FilamentManager.php:580-592). Le défaut actuel est ui-avatars.com, un service externe (UiAvatarsProvider.php:29).

## Pièges relevés
1. **Conversions mises en file** : `queue_conversions_by_default` vaut true (media-library.php:92). La connexion de file par défaut est `database` (queue.php:16) et aucun worker ne tourne dans DDEV (.ddev/config.yaml:263 commenté, aucun `queue:work`). Les tests forcent `sync` (phpunit.xml:34), donc ils ne verraient pas le problème. Il faut `->nonQueued()`.
2. **Chemins devinables** : `DefaultPathGenerator` range les fichiers sous l'id numérique du média (media-library.php:144 ; DefaultPathGenerator.php:36-44). La table a une colonne `uuid` unique (create_media_table.php:15). On peut brancher un générateur sur cette colonne via `custom_path_generators` (media-library.php:154-158).
3. **URL tirée d'APP_URL** : le disque public construit ses URL depuis `APP_URL` (filesystems.php:44) et `DefaultUrlGenerator` passe par `disk->url()` (ligne 12). L'URL ne suit donc pas l'hôte de la requête.
4. **`public/storage` n'est pas un lien symbolique** : c'est un vrai dossier, sur l'hôte comme dans le conteneur, qui contient seulement `app/public/` vide. Une photo enregistrée ne serait pas servie, et `storage:link` échouera. Cause supposée : `upload_dirs: storage/app/public` résolu par DDEV depuis le docroot `public` (.ddev/config.yaml:3,12-13). À confirmer, et la correction sort du périmètre de cette tâche.
5. **Test de login à adapter** : la connexion renvoie aussi `UserResource` (LoginController.php:24), et `AuthApiTest` compare la réponse exacte (AuthApiTest.php:40-50). Ajouter `avatar_url` le fera échouer.
6. **Taille maximale** : medialibrary accepte jusqu'à 10 Mo (media-library.php:53). La limite de 5 Mo doit être posée par la validation.
7. **HEIC** : le pilote d'image est `gd` (media-library.php:245).
   - Conteneur : `gd` ne déclare pas de support HEIF ; `imagick` lit HEIC.
   - Hôte (où tournent les tests) : pas d'`imagick`.
8. **Optimiseurs** : `cwebp`, `jpegoptim` et les autres sont absents du conteneur. La compression WebP doit venir de l'encodage lui-même.

## Fichiers lus
Liste complète dans `fichiers_lus` de la spec. En plus du code : `ddev exec` en lecture seule (ls, `php -r`, `command -v`, ps) et `php -r` sur l'hôte.

## Non couvert
- `.env` non lu (garde-fou) : valeurs réelles d'APP_URL, QUEUE_CONNECTION, MEDIA_DISK et IMAGE_DRIVER inconnues.
- Suppression des médias à la suppression du compte (`InteractsWithMedia`) : non vérifiée dans le vendor. Le critère 8 reste à prouver par un test.
- Méthodes des composants Filament medialibrary : non lues.
- Comportement de l'optimiseur quand ses binaires manquent : non vérifié.
- Non relus : `UserFactory`, le seeder, Logout, `LoginRequest`, le module Form.
- Skills `bza-dev:audit-source` et `bza-laravel:audit-laravel` non chargés : l'enveloppe de la spec est reconstituée.
