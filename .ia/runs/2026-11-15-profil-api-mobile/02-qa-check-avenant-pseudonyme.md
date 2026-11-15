# Verdict qa-check — étape 1, avenant « name pseudonyme »

- **Date** : 2026-11-15
- **Verdict** : GO — 0 bloquant, 0 majeur, 1 mineur
- **Preuves rejouées** : `php artisan test` 35/35 (124 assertions) ; migration
  `2026_11_15_170000` exécutée sous DDEV ; index `users_name_unique`, colonne NOT NULL,
  2 comptes, 2 pseudonymes distincts ; aucune migration existante modifiée ;
  registre : « Remplace » / « Remplacée par » conformes.

## Mineur (non traité)
- La reprise ne tronque pas à 255 caractères un nom très long suffixé (improbable).

## Ne couvre pas
Rendu visuel, tri et recherche en exécution ; données de production ; réponses de
l'API (étape 2) ; `composer.json`/`composer.lock` (Scramble, commité avec l'étape 2) ;
`down()` hors MariaDB.
