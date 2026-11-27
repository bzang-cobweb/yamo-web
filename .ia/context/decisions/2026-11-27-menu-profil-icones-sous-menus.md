# `2026-11-27` — Menu du profil : une icône par lien, des sous-menus

- **Type** : décision
- **Question posée** : « Menu profile mettre une icône au début de chaque lien » ; « Lien personal information sur menu
  profile ouvre une autre page qui a aussi un menu ; déplacer les liens email, password, residential address,
  emergency contact, identity verification dans personal information ».
- **Ce qui est retenu** :
  - chaque lien servi porte `icon: {ios, android}` (SF Symbols sur iOS, Material ailleurs), choisi par le serveur ;
    les autres modules la passent à `ProfileMenuLinks::addList(icon: …)` ;
  - un lien peut ouvrir un sous-menu (`menu`), servi par `GET /api/v1/auth/me/menu/{menu}` : `{title, data}`, les liens
    comme le menu ; l'app l'ouvre sur `/profile/menu/[menu]` ;
  - « Informations personnelles » devient un sous-menu : « Mes informations » (l'ancien formulaire, clé `details`),
    e-mail, téléphone, mot de passe, vérification d'identité ; l'adresse de résidence et le contact d'urgence s'y
    ajoutent avec leurs formulaires ;
  - « Confidentialité » est un sous-menu ; « Supprimer mon compte » y passe et quitte le bas du profil.
- **Décidé seul (mode nuit)** : le téléphone reste dans « Informations personnelles » (la demande ne le nomme pas, mais
  il va avec l'e-mail) ; le libellé du lien sert de titre au sous-menu.
- **Portée** : module Auth (`ProfileMenuKey`, `ProfileMenuService`, `ProfileMenuLinks`, route), modules qui inscrivent
  des liens, app (`ProfileMenuLinks`, `ProfileSubMenuScreen`).
- **Tranché par** : humain (demande), session de nuit pour le détail
