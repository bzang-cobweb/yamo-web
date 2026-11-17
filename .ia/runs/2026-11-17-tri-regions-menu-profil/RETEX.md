# RETEX — 2026-11-17-tri-regions-menu-profil

- **Super administrateur et `before`** : `HasBeforeAbility` donne tout au super admin, y compris des actions qu'une policy veut interdire à tous (création, suppression de lignes fixes). Relevé par la vérification. Règle candidate (projet) : une policy qui interdit une action à tous la refuse dans `before`, et un test le joue avec un super admin.
- **Réordonnancement Filament** : il passe par l'ability `reorder` de la policy ; sans elle, l'appel `reorderTable` est ignoré sans erreur. Le test doit vérifier l'état en base, pas l'absence d'erreur.
- **Simulateur** : une requête refaite au retour sur un écran arrive après la capture ; attendre avant de conclure à un échec.
- Règle ré-implémentée : aucune. Règle domiciliée : le filtre par canal du menu vit dans `ProfileMenuKey::isAvailable` (via `AuthChannels`), plus dans l'écran de l'app.

## Correction demandée après clôture

- L'humain a revu deux choix du contrat validé : options de régions alphabétiques dans l'API (la position reste pour le back-office), menu du profil défini dans le code du service. Décision `2026-11-17-regions-alphabetiques-menu-en-code`. Leçon : « liste depuis le back-office » demandait à être reformulée en exemple concret au contrat (qui modifie quoi, où), pas seulement validée.
- Vérification de la correction : GO (`02-qa-check-correction.md`). Remarque laissée en suspens : `Collator` exige l'extension `intl`, non déclarée dans `composer.json` (déjà utilisée avant ce chantier) ; la déclarer est un changement de dépendances, à autoriser.
