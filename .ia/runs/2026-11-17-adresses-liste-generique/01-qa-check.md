RAPPORT QA — 2026-11-17-adresses-liste-generique, tâche 01 (vérification finale)
Go : oui

Base du verdict (rejoué moi-même) :
- web : `ddev exec php artisan test --compact` -> 300 passed (1443 assertions).
- web : `ddev exec vendor/bin/pint --test` -> PASS, 323 fichiers.
- web : `migrate:status` -> `2026_11_18_100000_create_addresses_table` [13] Ran.
- mobile : `npx jest` -> 30 suites, 136 tests passés. `npx tsc --noEmit` -> code 0. `npx expo lint` -> code 0.
- openapi : chemins de openapi.json comparés aux routes `v1` (sans régénérer). Les 3 routes d'adresses (POST, PATCH, DELETE), `GET /v1/lists/{list}`, `GET /v1/forms/{form}` et `GET /v1/auth/me/menu` sont dans les deux, avec les mêmes méthodes.
- Lecture du code, à la lumière des critères :
  - Unicité : `Address::fingerprint` (sha1 de la rue, du NPA et de la ville, sans casse ni espaces) est calculée à chaque `saving`. Le test `test_the_database_refuses_a_duplicate` est vert.
  - Pivot : `address_user` est unique sur la paire, avec suppression en cascade.
  - Règle : `AddressService` fait attach (trouver ou créer, puis pivot), replace (l'ancienne adresse n'est pas modifiée) et detach (pivot seul).
  - 404 : `AddressPolicy::owns` répond `denyAsNotFound`, garde `can:` sur les routes PATCH et DELETE.
  - Liste serveur : `AddressListing` déclare `create`, `edit` et `delete`, avec la suite `BACK`.
  - `record` : `FormController` le transmet à `forRecord`.
  - Back-office : relation managers du pays et de l'utilisateur, testés (doublon refusé, ajout, changement, retrait, permissions).
  - Liste de test : `ListingApiTest::test_a_new_list_is_served_without_anything_else` sert une liste déclarée côté serveur seul.
  - Mobile : `ProfileListScreen` générique. Les tests couvrent le vide, la création, l'édition, le retrait avec confirmation, l'annulation, le refus, la lecture seule et le lien de menu `list`. Le lien de menu vide est ignoré.
- `git status` propre sur les deux dépôts après mes essais.

Ne couvre pas :
- L'essai dans le simulateur iOS, avec compte jetable (interdit dans cette tâche) : le comportement réel de l'écran n'est établi que par les tests jest.
- Le rendu visuel du back-office Filament, vu seulement par les tests Livewire.
- Une base MySQL ou MariaDB réelle : tests en sqlite mémoire. La migration est au statut Ran dans la base DDEV.
- Le contenu exact de openapi.json (schémas, exemples) : seuls les chemins et les méthodes ont été comparés.
- La compatibilité des anciennes versions de l'app avec un lien de menu sans `form`.
- Le français et l'anglais n'ont pas été relus ligne à ligne.
- Le commit mobile d44ebb2 est signé « Claude Opus 5.5 » : l'attribution n'est pas un critère.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Le contrat dit « Index unique sur (country_id, street, zip, city) ». L'implémentation pose l'unicité sur `(country_id, fingerprint)` : `Modules/Location/database/migrations/2026_11_18_100000_create_addresses_table.php:35`. C'est équivalent et nécessaire pour ignorer la casse, et le fichier le documente. À noter dans la clôture ou la décision.
- Une saisie dont l'adresse existe déjà ignore la latitude et la longitude fournies : `AddressService::findOrCreate` garde celles de l'adresse existante. C'est documenté dans le code, mais absent du contrat.
