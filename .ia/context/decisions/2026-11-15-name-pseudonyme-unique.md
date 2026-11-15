# `2026-11-15` — La colonne `name` est conservée comme pseudonyme unique

Remplace : 2026-11-15-contrat-profil-api-mobile.md (point A uniquement ; B et C restent en vigueur)

- **Type** : décision
- **Question posée** : que devient la colonne `name` une fois `first_name` et `last_name` ajoutés ?
- **Réponse** : `name` est conservé, obligatoire et unique ; il sert de pseudonyme. `first_name` et `last_name` restent l'identité civile.
- **Motif** : le pseudonyme est l'identité affichée dans l'application (demande de l'humain, 2026-11-15, en cours de chantier).
- **Portée** : table `users`, modèle, ressource Filament, commande `auth:create-user`, réponses de l'API.
- **Corollaires** : la colonne, supprimée par la migration `2026_11_15_160000`, est remise par une nouvelle migration (une migration exécutée ne se modifie pas) ; les comptes existants reçoivent « prénom nom » comme pseudonyme, avec un suffixe numérique en cas de doublon ; le back-office continue d'afficher « prénom nom » pour l'utilisateur connecté ; `make:filament-user` reste inutilisable (prénom et nom obligatoires) : `auth:create-user` reçoit une option `--name`.
- **Tranché par** : humain
