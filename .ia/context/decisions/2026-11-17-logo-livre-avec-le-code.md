# `2026-11-17` — Le logo est livré avec le code, plus un réglage du back-office

- **Type** : décision
- **Modifie** (point logo seulement, le reste de ces contrats tient) :
  `2026-11-19-contrat-accueil-description-regions.md` (logo dans les réglages de
  l'application) et `2026-11-20-contrat-fil-ariane-menu-logo-traduction.md`
  (barre du haut alimentée par `GET /api/v1/app`).
- **Question posée** : une fois le logo YaMo dessiné, faut-il garder le champ
  « logo » de la page Application du back-office ?
- **Réponse** : « il faut enlever le champ logo en backend et passer les fichiers
  directement ».
- **Motif** : le logo est l'identité de la marque, fixe et décliné en plusieurs
  fichiers (symbole, mot, horizontal, vertical, fond clair ou sombre, icône d'app).
  Un champ unique ne porte pas ces déclinaisons, et l'app mobile n'a pas à
  attendre le réseau pour afficher sa marque.
- **Portée** :
  - Web : champ et collection de médias `logo` retirés de `Setting` et de la page
    Application ; logos déjà envoyés supprimés par migration ; `logo_url` retiré
    de `GET /api/v1/app` ; la page `/` affiche
    `public/images/logo/yamo-logo-vertical-{couleur,inverse}.svg`.
  - Mobile : les fichiers du logo sont embarqués dans l'app (assets), pas lus
    depuis l'API. La barre fixe du haut affiche **toujours le mot seul**
    « YaMo » (choix de Bertrand) ; l'accueil invité, le logo vertical ;
    l'écran hors ligne, le symbole. Icônes, splash et images des stores sont
    générés depuis les mêmes fichiers.
  - Ne couvre pas la description de l'application, qui reste un réglage traduit.
- **Corollaires** :
  - Changer le logo demande une livraison du code (web) et une nouvelle version
    de l'app (mobile), plus un envoi depuis le back-office.
  - Tant que l'app mobile lit encore `logo_url`, elle affiche le nom de
    l'application à la place du logo (la clé est absente, donc vide).
  - Les sources des fichiers sont dans `public/images/logo/` ; l'app mobile en
    garde une copie.
- **Tranché par** : humain (Bertrand Zang), en conversation, le 2026-11-17.
