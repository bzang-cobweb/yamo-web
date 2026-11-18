RAPPORT QA — 2026-11-18-useraddress-dans-auth
Go : oui (avec réserve : les 2 tests de pagination n'ont pas pu être rejoués sur HEAD)
Base du verdict : rejoué moi-même.
- web, `ddev exec php artisan test` : 321 passés, 2 échoués (1581 assertions). Les 2 échecs sont « 45 addresses are served 20 at a time » et « cursors follow the search », dans UserAddressApiTest. Ils attendent [20,5] et reçoivent des pages de 4. Cause : `Listing::perPage()` renvoie 4 dans le fichier de travail, contre 20 dans `git show HEAD:Modules/Form/app/Support/Listing.php`. C'est la modification locale non commitée de l'humain, hors chantier.
- Pint `--test` : PASS, 333 fichiers.
- OpenAPI : `scramble:export --fail-on-unknown` réussi, `cmp` avec openapi.json identique. Le fichier temporaire est supprimé.
- `migrate:status` : 0 migration en attente. Les deux migrations d'adresses sont « Ran ». `2026_11_18_120000_turn_address_user_into_user_addresses` est renommée de Location vers Auth à 100 % (0 ligne changée), nom de fichier inchangé. `migrate:fresh` est couvert par les tests RefreshDatabase, qui passent.
- mobile (d115a9c) : jest 33 suites et 171 tests passés. `tsc --noEmit` sans erreur. Lint : 0 erreur, 1 warning (router.d.ts, déjà présent, hors chantier).
- Critère 1 : grep de `UserAddress|user_addresses|recipient|destinataire|address_user|genre|prénom|nom` dans Modules/Location. Il ne reste que la migration historique `create_addresses_table` (qui crée `address_user`) et des mentions du nom de la décision dans des commentaires, sans référence au modèle. Location n'importe depuis Auth que `User`, `PermissionEnum`, `HasBeforeAbility` et les cas de test Filament. Il n'y a plus aucun import lié aux adresses de compte.
- Critère 2 : comparaison des noms de méthodes de test entre 29855cc~1 et HEAD (314 avant, 318 après). Un seul nom disparaît : `test_purging_the_account_or_deleting_the_country_removes_the_rows`. Il est remplacé par `..._removes_its_entries` dans UserAddressServiceTest (même scénario, côté Auth) et par `test_deleting_the_country_removes_its_addresses` dans Location, qui couvre la suppression de l'adresse. Quatre ajouts nets dans AddressServiceTest et UserAddressServiceTest. Aucun test perdu. Les routes `api.v1.auth.me.addresses.*` n'ont pas été rejouées une à une : les tests d'API déplacés passent.
- Recherches complémentaires : plus aucune référence à `location::address.form|list|recipient|detach` ni à `location.address` dans app, Modules, resources, lang et mobile/src. Les noms publics sont `auth.address` et `auth.addresses` (option A). Les enregistrements sont uniques : policy, FormRegistry et ListingRegistry dans AuthServiceProvider seulement. LocationServiceProvider ne garde que `Address` et `AddressPolicy`. Les classes correspondent à leurs fichiers. La détection d'imports morts est un script grossier sur les fichiers modifiés et n'a rien remonté. Les 323 tests sont tous découverts. Les 3 nouveaux fichiers de test d'Auth sont bien exécutés.

Ne couvre pas :
- Les 2 tests de pagination sur la version commitée de Listing.php. Mon essai (`git archive HEAD` copié dans storage/app + vendor) a donné 18 avertissements `file_get_contents`, donc inexploitable. Le dossier temporaire est supprimé. La preuve indirecte est que ces 2 tests échouent uniquement sur la valeur 4 contre 20, et que HEAD contient 20.
- Un `migrate:fresh` sur la base locale DDEV (interdit). Le comportement réel dans un navigateur ou l'app mobile. Un contrôle manuel du back-office. Les branches autres que main.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Modification locale non commitée de `Modules/Form/app/Support/Listing.php` (perPage 4). À annuler avant de livrer, sinon la suite web reste rouge.
- Le test `..._removes_the_rows` a été renommé et scindé. Ce n'est pas une perte, mais le nom a changé.
