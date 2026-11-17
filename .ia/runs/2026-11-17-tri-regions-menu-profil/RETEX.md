# RETEX — 2026-11-17-tri-regions-menu-profil

- **Super administrateur et `before`** : `HasBeforeAbility` donne tout au super admin, y compris des actions qu'une policy veut interdire à tous (création, suppression de lignes fixes). Relevé par la vérification. Règle candidate (projet) : une policy qui interdit une action à tous la refuse dans `before`, et un test le joue avec un super admin.
- **Réordonnancement Filament** : il passe par l'ability `reorder` de la policy ; sans elle, l'appel `reorderTable` est ignoré sans erreur. Le test doit vérifier l'état en base, pas l'absence d'erreur.
- **Simulateur** : une requête refaite au retour sur un écran arrive après la capture ; attendre avant de conclure à un échec.
- Règle ré-implémentée : aucune. Règle domiciliée : le filtre par canal du menu vit dans `ProfileMenuKey::isAvailable` (via `AuthChannels`), plus dans l'écran de l'app.
