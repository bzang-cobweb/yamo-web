RAPPORT QA — 2026-11-20-type-de-location (commit eae936c)
Go : oui
Base du verdict : rejoué moi-même.
- `ddev artisan test` : 579 passés, 3151 assertions, 0 échec (dont RentalTypeMigrationTest).
- Relecture du code : RentalDomain (attributes rental_type required, bookingType STAY/VISIT, priceUnit/priceUnitWhen, charges visibleWhen long_term+shared, filters + applyFilters), RentalTypeEnum, OfferNotificationFilter (champ rental_type), migration 2026_11_30_120000, DemoSeeder.
- Filtres : browse, mine et favoris passent tous par Lists/Concerns/FiltersOffers.php -> Domain::filters()/applyFilters (RentalDomain.php:242) ; notifications : OfferNotificationFilter. Tests OfferFiltersApiTest:104-105 et NotificationFiltersTest:83-84 vérifient la restriction.
- grep `furnished` hors tests (Modules, app, database, config, routes, resources) : plus de `details.furnished` ; seules occurrences = valeur d'enum rental_type, libellés de langue, variable locale du seeder. DemoSeeder ne crée plus de catégories ; Domain::categories() vide, OfferDatabaseSeeder ne seed que ce que le domaine renvoie.
- Migration : catégorie d'abord, sinon furnished=true -> furnished, sinon rental_type existant ou long_term ; supprime details.furnished ; category_id remis à null pour les 3 slugs ; enfants remontés à la racine (parent_id null) puis 3 catégories supprimées ; autres catégories conservées. Test de migration couvre ces cas (catégorie prime sur « Meublé », autre catégorie gardée, enfant remonté).
- `scramble:export --fail-on-unknown` : exit OK (2 avertissements, sur des ressources non liées). Comparé à openapi.json : rental_type (enum 3 valeurs, requis dans OfferRequest) identique ; seules différences : birth_date/gender du profil utilisateur, hors chantier.
- Dépendances : composer.json, composer.lock, package*.json non modifiés par eae936c. Arbre git propre.
Ne couvre pas : app mobile ; exécution réelle de la migration sur des données de production (aucun migrate permis, rejeu via test uniquement) ; rendu visuel Filament ; avertissements Scramble et écart birth_date préexistants.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Aucun test ne vérifie explicitement le refus (422) d'un `rental_type` absent à la modification API ni dans Filament ; le caractère obligatoire repose sur `->required()` (RentalDomain.php, attributes) et est testé seulement via `fields['rental_type']['required']` (OfferApiTest.php:118).
- Migration : une offre rattachée à une sous-catégorie des 3 catégories de location n'est pas convertie d'après son parent ; elle retombe sur « Meublé » / long_term (conforme à la lettre du contrat, à noter).
- Note : openapi.json committé n'a plus `category_id` (base locale sans catégories) ; pas de problème caché constaté, rental_type est bien présent.
