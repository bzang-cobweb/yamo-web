RAPPORT — 2026-11-16-textes-servis-par-api / tâche 01 (audit web)
Livrable      : .ia/memory/spec_formulaires_textes_web.json (JSON valide, `jq empty` OK) ; chaque affirmation est citée fichier:ligne.
Preuve        : relire la spec ; chaque citation se rejoue avec `sed -n '<l>p' <fichier>` ou `grep -n`.
Périmètre     : écrit uniquement la spec et ce rapport ; rien n'est commité (consigne).
Déplacements  : aucun
Non couvert   : voir plus bas.
Effets de bord: aucun
Règle générale: « un texte affiché vient d'un fichier de langue » vit dans .ia/context/project.md (Conventions). Le 401 la viole parce que le message est une chaîne littérale du framework, rendue sans passer par __() (Handler.php:857). La règle n'a aucun domicile pour les messages d'exception.
Reste ouvert  : les incertitudes de la spec (ordre SetLocale / auth:sanctum, à confirmer par un test).

## Utile à l'implémentation

- **Form** (Modules/Form/app/Support/Form.php) n'a aujourd'hui ni title() ni subtitle(). FormResource.php:20-27 renvoie {name, fields, submit}. Son phpdoc (:18) alimente le schéma OpenAPI (openapi.json:370-400) : mettre les deux à jour.
- **Test cassé par title et subtitle** : Modules/Auth/tests/Feature/Api/FormsAndLocalesApiTest.php:29-42, qui fait un assertExactJson sur auth.login en fr. Aucun autre assertExactJson ne porte sur un formulaire. FieldTest.php:18-46 définit une classe anonyme de Form : title et subtitle doivent rester facultatives, sinon ce test casse.
- **help** existe déjà (Field.php:109, sérialisé :191) et n'est utilisé nulle part. ProfileLocaleForm.php:24-28 est l'endroit où l'ajouter.
- **Traductions des modules** : le boot() de ModuleServiceProvider appelle registerTranslations() (vendor/nwidart/.../ModuleServiceProvider.php:60, 121-133), qui déclare le namespace `<nameLower>` sur Modules/<Name>/lang. Résultat : `auth::` (AuthServiceProvider.php:51) et `form::` (FormServiceProvider.php:48).
  - **Modules/Form n'a pas de dossier lang/**. Il suffit de le créer : le namespace est déclaré même si le dossier manque (ServiceProvider.php:251-256).
  - `__('auth::mobile')` renvoie le tableau du fichier entier (Translator.php:285-299). Pour aplatir : Arr::dot.
  - Pour lister les modules, passer par Module::allEnabled() (FileRepository.php:277) et getLowerName() (Module.php:108). Les namespaces du loader contiennent aussi ceux de Filament.
- **Pièges de langue**
  - Le secours fr→en prend le groupe entier, sans fusion clé par clé (Translator.php:171-179) : un fichier fr incomplet n'est pas complété par l'anglais.
  - Les clés aplaties n'ont pas de préfixe de module : deux modules qui déclarent la même clé s'écrasent.
  - lang/en n'a ni en.json ni http-statuses.php.
- **401 « Unauthenticated. »** : le message est en dur dans Authenticate.php:101-102. Handler::unauthenticated le renvoie en JSON via getMessage() (Handler.php:854-857), sans __(), et lang/fr.json n'a pas de clé « Unauthenticated. ». Il faut donc rendre ce message soi-même dans bootstrap/app.php (withExceptions, :20-24).
  - **Second 401** : un invité qui demande un formulaire réservé passe par Gate denyWithStatus(401) (FormServiceProvider.php:35). Le message rendu est alors « Unauthorized » (Handler.php:774-776, Symfony Response.php:151), pas « Unauthenticated. ».
  - **403** : « This action is unauthorized. » n'est pas traduit non plus, alors que la clé existe dans lang/fr.json:200.
  - Aucun test ne vérifie aujourd'hui le texte d'un 401.
- **RouteGuardTest** : ajouter `'api/v1/messages' => motif` à WITHOUT_CAN (tests/Feature/RouteGuardTest.php:16-20). Une route en closure échapperait au balayage (:29).
- **Médias** : UuidPathGenerator.php:14-27 renvoie `uuid/`, `uuid/conversions/` et `uuid/responsive-images/`. Il est configuré dans config/media-library.php:3 et :145.
  - Il n'y a pas de morph map (grep) : model_type contient le nom complet de la classe, d'où le module se déduit. Le nom du champ est collection_name, soit 'avatar' (AvatarService.php:18).
  - Tests à adapter : AvatarApiTest.php:56, :63-66, :79 et :93 (directories() vaudra ['auth']), et UserAvatarTest.php:46. UserAvatarTest :69 et :74-75 restent vrais.
  - L'ancien nom UuidPathGenerator apparaît aussi dans .ia/context/project.md.

## Ce que l'audit ne couvre pas
- **Skills introuvables** : bza-dev:audit-source et bza-laravel:audit-laravel ne sont pas sur le disque. J'ai repris l'enveloppe des specs existantes en lisant seulement leurs clés, pas leur contenu. La profondeur réduite est signalée dans la spec.
- **Rien n'a été exécuté** : ni artisan ni les tests. Les namespaces chargés à l'exécution et l'ordre des middlewares sont déduits de la lecture du code, pas observés.
- **Fichiers lus en partie** :
  - lang/*/validation.php, lang/fr/actions.php, pagination et passwords : lus seulement par recherche ciblée ;
  - config/media-library.php, config/app.php et config/modules.php : lus par extraits ;
  - AuthApiTest.php : extrait :25-60 seulement.
- **Non relus** : le schéma Filament du champ avatar et AvatarService au-delà de ses constantes. Ils sont couverts par le chantier photo de profil.
- **Méthode de relevé** : grep sur `help(`, `UuidPathGenerator`, `morphMap`, `assertExactJson`, `Unauthenticated` et `assertUnauthorized`. Une référence écrite autrement (chaîne construite à l'exécution, alias) n'a pas pu être trouvée.
