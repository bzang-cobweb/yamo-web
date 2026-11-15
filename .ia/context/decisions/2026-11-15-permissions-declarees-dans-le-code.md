# `2026-11-15` — Les permissions sont déclarées dans le code

- **Type** : décision
- **Question posée** : où vivent les permissions vérifiées par les policies ?
- **Réponse** : dans `Modules/Auth/app/Enums/PermissionEnum.php`, synchronisées en base par `AuthDatabaseSeeder` (rejouable, supprime les permissions qui ne sont plus déclarées). Le back-office les affiche en lecture seule : pas de création, modification ni suppression. Les rôles, eux, se gèrent librement dans le back-office.
- **Motif** : une permission n'a de sens que si le code la vérifie ; une permission créée à la main dans l'admin ne protège rien, et une permission supprimée à la main casse une policy.
- **Portée** : toutes les permissions de tous les modules. Ne couvre pas les rôles.
- **Corollaires** : ajouter une permission = ajouter un cas à l'énumération + sa traduction (`Modules/Auth/lang/{fr,en}/permission.php`, clés imbriquées par groupe) + relancer `ddev artisan db:seed`.
- **Tranché par** : orchestrateur (à valider par l'humain)
