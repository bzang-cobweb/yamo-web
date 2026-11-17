# `2026-11-17` — Régions ordonnées ; menu du profil géré dans le back-office

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-17-tri-regions-menu-profil.md`. Où s'applique l'ordre des régions ? Que définit le back-office pour le menu du profil ?
- **Réponse** : « Everywhere regions list then add order by in regions function in Model Country » ; « définir une route api qui passe par un service qui retourne cette liste » ; contrat validé (« Validated »).
  - Régions : colonne `position`, glisser-déposer dans l'onglet Régions d'un pays, `Country::regions()` et le champ Région suivent cet ordre.
  - Menu du profil : les cinq écrans de l'app, en base ; le back-office règle l'ordre et l'activation, sans création ni suppression. `GET /api/v1/auth/me/menu` passe par `ProfileMenuService`.
  - Défauts du contrat non contestés : libellés dans les traductions du serveur (non modifiables dans le back-office) ; pas de lien vers une page web.
- **Motif** : ordonner les régions comme l'administrateur l'entend, et piloter le menu du profil sans nouvelle version de l'app.
- **Portée** : module Location (régions), module Auth (menu du profil, formulaire Informations personnelles), app mobile (Profil).
- **Corollaires** :
  - l'ordre alphabétique du champ Région disparaît ;
  - un canal désactivé dans la config retire son lien quel que soit le réglage du back-office ;
  - un nouvel écran du profil demande une migration qui ajoute sa ligne.
- **Tranché par** : humain
