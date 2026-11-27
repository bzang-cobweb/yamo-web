# Nuit du 2026-11-27 — Yamo
Branche : nuit/2026-11-27 (web depuis main@336f312, mobile depuis main@5888a1b)
État : interrompue (en cours)

## Ce qui attend votre attention
- **Visibilité du profil** : choix en boutons radio (et non interrupteurs : un seul choix possible) ; « contacts » =
  une demande en commun ou un échange de messages ; un profil caché répond 404. Elle ne vaut que pour la page publique
  d'un membre, pas pour son nom dans les avis, notifications ou partenaires.
- **Mes données** : seulement les données du compte (module Auth), pas les offres, demandes, avis ni messages.
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
- **Lot B — formulaires** (sous-agent, rapport `B-formulaires.md`) : « Notifications » (activité du compte et rappels
  par canal, offres, nouveautés ; « Activité du compte » appliquée à l'e-mail et au SMS de toutes les notifications),
  vérification d'identité (note, pays, type de pièce, photos), adresse de résidence, contact d'urgence. Web `653a97b`,
  `15d8185`, `6c15f5c`, `93c699c`. Preuves : suite web complète 972 tests verts, migrations jouées, Pint propre.
- **Lot B — masquage, vérification, confidentialité** (sous-agent, rapport `B-confidentialite.md`) : e-mail et numéro
  masqués à l'affichage (profil, formulaires, membres d'un partenaire, contact d'une demande ; le lien d'action garde la
  vraie valeur ; back-office non masqué) ; « Vérifier mon adresse » / « Renvoyer le code » quand l'actuel n'est pas
  vérifié (`Form::links()`, nouveau mécanisme du paquet form) ; « Confidentialité » : visibilité du profil (respectée
  par la page publique d'un membre), lieu sous les avis, « Mes données » (JSON par e-mail), suppression avec raison et
  code, puis déconnexion. Web `1097118`, `fc97d96`, `4782e7f` ; mobile `d0474c8`, `778d90a`, `c817da5`. Preuves : web
  993 tests verts ; mobile tsc propre, 74 suites / 470 tests verts.

## Reste à faire
- Demande `specs/todo/2026-11-27-yamo-visiteur-profil-favoris.md` (copie de `~/Downloads/yamo.rtf`).

## Décidé seul
- La demande vient d'un fichier RTF hors du dépôt : copiée telle quelle en texte dans `specs/todo/` pour en garder la trace.

## Défauts trouvés
- Contact d'urgence : le champ « Langue » reprend la liste de « Préférences » (`config('form.locales')`), qui refuse
  l'allemand en test alors que l'app le propose ; noté, pas creusé.
- « Rappels », « Offres », « Nouveautés » et le push sont enregistrés mais aucun envoi ne les lit encore.
