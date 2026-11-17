Remplace : 2026-11-17-tri-regions-menu-profil.md

# `2026-11-17` — Régions alphabétiques dans l'API ; menu du profil défini dans le code

- **Type** : décision
- **Question posée** : retour sur le chantier `specs/done/2026-11-17-tri-regions-menu-profil.md`.
- **Réponse** : « la liste des régions envoyée par api doit être triée par ordre alphabétique mais on garde le tri » ; « le profil menu n'est pas gérer en backoffice mais au niveau du code dans le service ».
  - Régions : la `position` et le glisser-déposer du back-office restent, ainsi que `Country::regions()` trié par position. Les options du champ Région servies par l'API sont triées par ordre alphabétique, selon la langue.
  - Menu du profil : la liste et son ordre sont écrits dans `ProfileMenuService`. Plus de table, de modèle, de policy ni de page dans le back-office. La route `GET /api/v1/auth/me/menu`, le filtre par canal et les libellés selon le compte restent.
- **Motif** : l'humain veut un champ Région alphabétique pour l'utilisateur, et un menu qui change avec le code, pas depuis le back-office.
- **Portée** : module Auth (formulaire Informations personnelles, menu du profil), module Location (inchangé hors API).
- **Corollaires** :
  - changer le menu du profil demande une nouvelle version du serveur, mais pas de l'app tant que les écrans existent ;
  - la table `profile_menu_links` est supprimée par une nouvelle migration.
- **Tranché par** : humain
