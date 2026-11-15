# Verdict qa-check — étape 1 (profil : prénom, nom, langue)

- **Date** : 2026-11-15
- **Verdict** : GO — 0 bloquant, 0 majeur, 3 mineurs
- **Preuves rejouées par qa-check** : `php artisan test` 32/32 (109 assertions) ;
  `ddev artisan migrate:status` → `2026_11_15_160000_split_user_name_and_add_locale` Ran ;
  colonne `name` absente, 2 comptes repris (premier mot → prénom, reste → nom).
- **Preuve de la session** : le test `test_bulk_delete_never_deletes_its_own_account`
  échoue sans `authorizeIndividualRecords('delete')` (méthode de substitution) et passe avec.

## Mineurs (non traités)
- `php artisan migrate:status` sur l'hôte échoue (hôte `db`) : la base est celle de DDEV.
- Reprise du nom sans test automatisé ; nom d'un seul mot → `last_name` vide.
- Descriptions des options de `auth:create-user` en dur dans `$signature`.

## Ne couvre pas
Rendu navigateur (contrôlé à part par la session : liste et formulaire affichés),
exécution interactive de la commande, `down()` sur données réelles, bases autres que MariaDB.
