RAPPORT QA — 2026-11-18-role-membre
Go : oui
Base du verdict : rejoué moi-même dans DDEV : `php artisan test` = 345 passés (1707 assertions) ; `pint --test` = PASS (379 fichiers) ; `scramble:export --fail-on-unknown` vers storage/app puis `cmp` avec openapi.json = identique octet pour octet ; mobile `npx jest` = 33 suites / 171 tests passés, mobile sans modification (git status vide). Code relu : le rôle n'est attribué qu'à un seul endroit (`RegisterService::complete`, dans la transaction, guard `web`) ; les seuls autres `assignRole` sont `CreateUserCommand` (super_admin uniquement) ; rien dans le back-office. Migration 2026_11_22_120000 relue : up = rôle aux seuls comptes sans aucun rôle, comptes notés dans une table `member_role_backfill` ; down = retire aux seuls comptes notés puis supprime la table (test MemberRoleMigrationTest couvre sans-rôle, admin, membre inscrit après). Seeder : `RoleEnum::MEMBER` sans permission, `givePermissionTo` n'enlève rien ; test de rejeu présent.
Ne couvre pas : inscription par téléphone seul rejouée à part (même chemin `complete()`, tests e-mail seul et les deux) ; migration jouée sur une base de données réelle volumineuse ou sur la base DDEV (interdit) ; écran back-office de la fiche utilisateur vu dans un navigateur.

Bloquants : 0

Majeurs : 0

Mineurs : 1
- La table de suivi `member_role_backfill` reste en base après `up` (nécessaire au retour arrière, assumé et documenté dans la migration).
