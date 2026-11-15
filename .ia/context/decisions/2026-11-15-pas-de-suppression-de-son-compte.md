# `2026-11-15` — Personne ne supprime son propre compte

- **Type** : conséquence de conception
- **Découle de** : le court-circuit super administrateur (`HasBeforeAbility::before()`) donne tous les droits, y compris celui de se supprimer soi-même.
- **Ce que ça implique concrètement** : `UserPolicy::before()` refuse `delete` quand le compte visé est celui de l'utilisateur connecté, avant le court-circuit ; l'action « Supprimer » est masquée sur sa propre ligne, même pour un super administrateur.
- **Ce n'est pas un défaut** : empêche de perdre le dernier accès au back-office par erreur. Supprimer un compte d'administrateur se fait depuis un autre compte.
