# Rôle « membre », donné à l'inscription depuis l'app

Demande du 2026-11-18 :

> ajouter un Role membre qu'on assigne par défaut aux utilisateurs qui créent leur compte depuis mobile

## Ce que j'ai compris

- `RoleEnum` gagne `MEMBER = 'member'`. Le seeder le crée, **sans permission** pour l'instant. C'est le socle sur lequel les chantiers suivants ajouteront des droits, comme publier ses offres.
- **Le rôle est donné à l'inscription par l'app**, et seulement là : dans `RegisterService::complete()`, au moment où le compte est créé après la vérification du code, dans la même transaction.
- Un compte créé dans le back-office ou par la commande `CreateUserCommand` **ne reçoit pas** le rôle automatiquement.
- **Comptes déjà inscrits** : une migration donne `member` à chaque compte qui n'a **aucun** rôle. Un super administrateur ou un admin n'est pas touché. La migration sait aussi revenir en arrière : elle retire le rôle aux seuls comptes qu'elle a servis.
- Dans le back-office, le rôle `member` est proposé comme les autres dans la fiche d'un utilisateur. C'est déjà le comportement actuel pour tout rôle autre que super administrateur.

## Critères de succès

1. Un compte créé par le parcours d'inscription de l'API (e-mail ou téléphone) a le rôle `member`, et seulement celui-là.
2. Un compte créé dans le back-office ou par `CreateUserCommand` n'a pas `member`.
3. Après la migration, les comptes existants sans rôle ont `member`. Les comptes qui avaient déjà un rôle n'ont pas changé. Le retour en arrière retire `member` aux seuls comptes servis.
4. Rejouer le seeder ne retire rien au rôle.
5. Les tests web passent, Pint est propre et `openapi.json` est à jour (aucun changement attendu).

## Hors périmètre

- Des permissions pour `member` (elles viendront avec les offres).
- Des écrans ou un comportement de l'app qui dépendraient du rôle.

## Ce que j'ai décidé seul

- **Le rôle est créé s'il manque** au moment de l'inscription (`Role::findOrCreate`, guard `web`). Ainsi, l'inscription ne dépend pas d'un seeder rejoué. La guard est fixée parce qu'une requête d'API aurait sinon pris `sanctum`.
- **Une table de suivi `member_role_backfill`** note les comptes servis par la migration, pour que son retour en arrière ne retire le rôle qu'à eux. Elle reste en base tant que la migration est appliquée.

## Ce qui a été fait

- Commit `4c82387` :
  - `RoleEnum::MEMBER`, attribué dans `RegisterService::complete()` ;
  - la migration `2026_11_22_120000_give_member_role_to_accounts_without_role` ;
  - les tests : inscription par e-mail et par les deux canaux, `CreateUserCommand` sans rôle, migration aller-retour, seeder.
- En base locale, la migration est jouée. Aucun compte ne l'a reçue : les deux comptes existants sont super administrateurs.
- QA (`bza-dev:qa-check`) : GO (`.ia/runs/2026-11-18-role-membre/qa-check.md`).

## Ce que je n'ai pas vérifié

- L'inscription par téléphone seul, en test dédié. Elle passe par le même `complete()`.

## Ce qui reste en suspens

- Rien n'est poussé.
