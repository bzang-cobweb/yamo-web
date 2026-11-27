# Contrat — Connexion sans mot de passe, profil enrichi, listes de favoris

Validation : non — exécuté en mode nuit le 2026-11-27

## Demande

`specs/todo/2026-11-27-yamo-visiteur-profil-favoris.md` (copie de `~/Downloads/yamo.rtf`) : trois blocs, « Visiteur »,
« Utilisateur connecté », « Favoris ».

## Ce que j'ai compris, et le découpage

### Lot A — Visiteur (Auth, app)
- **A1 Connexion sans mot de passe** : le formulaire `auth.login` ne demande que l'e-mail ou le téléphone (indicatif du
  pays courant d'office). Compte trouvé → code envoyé → page du code (comme aujourd'hui, sans le mot de passe). Compte
  inconnu → l'API répond `next: register` avec l'identifiant ; l'app ouvre l'inscription réduite à sa première étape
  (pseudonyme, prénom, nom, date de naissance) et envoie l'identifiant avec ; un code part ; page du code ; compte créé.
- **A2** : plus de lien « Mot de passe oublié ? » ; plus de lien « Créer un compte » (la connexion inscrit).
- **A3** : onglets Notifications et Profil d'un visiteur : le titre de l'onglet, un message d'invitation, un bouton
  « Se connecter ou s'inscrire » qui ouvre la connexion. Remplace la connexion intégrée posée le 2026-11-27.
- **A4** : plus de genre dans aucun formulaire (app et back-office) ; la colonne reste.
- **A5** : plus de description dans l'inscription.

### Lot B — Utilisateur connecté (Auth, app)
- **B1** plusieurs numéros de téléphone ; **B2–B4** e-mail et téléphone masqués partout où ils s'affichent, code envoyé à
  l'adresse ou au numéro même connecté, lien « Renvoyer le code » quand non vérifié ; **B5** `phone_verified_at` posé
  à la connexion par téléphone ; **B6** une icône devant chaque lien du menu ; **B7** « Confidentialité » (visibilité du
  profil, avis : pays, région, ville, données : demander mes données, supprimer mon compte avec message obligatoire et
  code) ; **B8** « Notifications » (activité du compte et rappels : e-mail, SMS, push, tous cochés ; offres et
  nouveautés : oui/non, oui par défaut) ; **B9** vérification d'identité : note, pays, type de pièce, fichiers ;
  **B10** adresse de résidence ; **B11** contact d'urgence ; **B12** « Informations personnelles » ouvre une page avec son
  propre menu (e-mail, mot de passe, adresse de résidence, contact d'urgence, vérification d'identité).

### Lot C — Favoris (Offer, app)
- **C1** listes de favoris nommées (`wishlists`) ; le cœur ouvre un panneau des listes avec « Créer » en bas ; une
  liste « Récemment consultés » par défaut, remplie à l'ouverture d'une fiche, absente de ce panneau ;
- **C2** une liste ouverte : menu ⋮ dans la barre du haut, « Renommer » et « Supprimer » ;
- **C3** bloc « Récemment consultés » à l'accueil, avant « À la une » ;
- **C4** lien vers cette liste : deux cartes par ligne, une croix pour retirer.

Ordre : A (il change l'entrée dans l'app), puis B dans l'ordre B12, B6, B2–B5, B8, B7, B9–B11, B1 ; C en parallèle
(périmètre disjoint : module Offer, `features/offer`, `features/list`).

## Critères de succès
- Un identifiant connu reçoit un code et se connecte sans mot de passe ; un inconnu s'inscrit avec la seule étape
  d'identité puis le code ; aucun formulaire ne montre le genre ni « Mot de passe oublié ».
- Chaque lien demandé existe dans le menu du profil ou dans « Informations personnelles », avec son formulaire, et ce
  qui est saisi est enregistré.
- Les e-mails et numéros affichés sont masqués.
- Un membre range une offre dans une liste choisie ou créée à la volée ; renomme et supprime une liste ; voit ses
  offres récemment consultées à l'accueil et dans leur liste, et en retire.
- Web : tests verts, Pint propre ; mobile : tsc sans erreur, Jest vert (« Test Suites: » compris).
