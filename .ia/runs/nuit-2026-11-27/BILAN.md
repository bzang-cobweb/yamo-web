# Nuit du 2026-11-27 — Yamo
Branche : nuit/2026-11-27 (web depuis main@336f312, mobile depuis main@5888a1b)
État : interrompue (en cours)

## Ce qui attend votre attention
- **Rien n'a été vu dans le simulateur** pour les favoris (panneau du cœur, menu ⋮, deux colonnes, bloc de l'accueil).
- La table `offer_favorites` reste en base mais n'est plus lue (favoris copiés dans une liste « Favoris » par membre) :
  à supprimer plus tard, par vous.
- **WhatsApp** : aucun envoi WhatsApp n'existe ; il faut choisir un fournisseur et ses identifiants. Défaut appliqué :
  le code part par SMS (décision 2026-11-27-connexion-sans-mot-de-passe).
- **Sécurité corrigée** : sans mot de passe, un numéro recyclé aurait ouvert le compte inactif de l'ancien titulaire ;
  un compte inactif ne se connecte plus par SMS (par e-mail, si).
- Nuit ouverte avec un dossier non suivi dans le web : `database/migrations/migration-viewer/` (travail d'une autre
  session). Il n'est ni touché, ni commité ; la création de branche le laisse en place.

## Fait
- **Lot A — Visiteur** (contrat `specs/2026-11-27-visiteur-profil-favoris.md`) : connexion sans mot de passe,
  inscription à la suite (une page), plus de « Mot de passe oublié ? », invitation dans les onglets d'un visiteur,
  plus de genre ni de description. Web `a1c30a9` (Auth : 250 tests verts, Pint propre) ; mobile `b165aa4` (tsc propre,
  tests auth 133 verts). Le mobile comprend déjà les icônes et sous-menus du profil (lot B, côté serveur à suivre).
- **Lot B — menu du profil** : une icône par lien, sous-menus « Informations personnelles » et « Confidentialité »
  (« Supprimer mon compte » y passe). Web `5c13a99` (Auth 253 tests verts).
- **Lot C — Favoris** (sous-agent, rapport `C-favoris.md`) : listes nommées, panneau du cœur (choisir ou créer),
  « Récemment consultés » (remplie à l'ouverture d'une fiche, 50 au plus, bloc à l'accueil avant « À la une », deux
  cartes par ligne avec une croix), menu ⋮ « Renommer » / « Supprimer ». Web `1b859a3`, `65a98ab` ; mobile `e12e372`,
  `e218784`. Preuves : web Offer + form 396 tests verts ; mobile tsc propre, 72 suites / 462 tests verts.

## Reste à faire
- Demande `specs/todo/2026-11-27-yamo-visiteur-profil-favoris.md` (copie de `~/Downloads/yamo.rtf`).

## Décidé seul
- La demande vient d'un fichier RTF hors du dépôt : copiée telle quelle en texte dans `specs/todo/` pour en garder la trace.

## Défauts trouvés
