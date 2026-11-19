Remplace en partie : 2026-11-20-pays-unique-identifiant-double.md (la liste n'est plus désactivée).

# `2026-11-19` — Back-office : le pays d'une offre se montre obligatoire

- **Type** : décision
- **Question posée** : « pays obligatoire en backoffice formulaire offre ».
- **Constat** : le champ était déjà obligatoire, mais un seul pays actif le désactivait, et Filament masque l'astérisque d'un champ désactivé.
- **Ce qui est retenu** : avec un seul pays actif, les listes de pays du back-office (`SingleCountry::lock` : offre, utilisateur, téléphone, adresse) restent pré-remplies et ne peuvent pas être vidées, mais ne sont plus désactivées ; elles ne proposent que les pays actifs, donc ce seul pays, et un autre est refusé. L'astérisque d'un champ obligatoire s'affiche.
- **Motif** : la demande de l'humain.
- **Portée** : `Modules/Location/app/Filament/Forms/SingleCountry.php`.
- **Corollaires** : la liste a l'air ouverte mais n'offre qu'un choix.
- **Tranché par** : humain (demande) ; moyen (ne plus désactiver) par l'agent
