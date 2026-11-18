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
