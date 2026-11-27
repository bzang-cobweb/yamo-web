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

## Ce que j'ai décidé seul
- **Connexion** : l'identifiant inconnu reçoit 200 `next: register` et l'app renvoie l'identifiant avec l'inscription
  (pas d'état côté serveur) ; WhatsApp n'existe pas → le code part par SMS (escalade, défaut appliqué) ; un compte
  inactif ne se connecte plus par SMS (numéro recyclé) ; un numéro jamais vérifié se connecte par SMS et devient
  vérifié ; une connexion par code e-mail vérifie l'adresse.
- **Inscription et profil** : mot de passe facultatif en base, créé depuis le profil ; écrans « Créer un compte » et
  « Mot de passe oublié » retirés de l'app (routes d'API gardées) ; genre retiré des formulaires, colonnes gardées.
- **Menu** : icônes SF Symbols / Material servies ; le téléphone reste dans « Informations personnelles » ; « Supprimer
  mon compte » dans « Confidentialité ».
- **Confidentialité** : visibilité en boutons radio (choix unique), « contacts » = demande ou messages en commun, profil
  caché → 404 ; lieu sous les avis désactivé par défaut ; « Mes données » envoie un JSON des données du compte par
  e-mail ; suppression : raison gardée pour l'équipe, code à l'e-mail vérifié, sinon au téléphone.
- **Notifications** : seule « Activité du compte » est appliquée (e-mail, SMS) ; le reste est enregistré.
- **Numéros** : le principal reste `users.phone` ; les autres dans `user_phones`, vérifiés à l'ajout ; « Rendre
  principal » sans nouveau code.
- **Favoris** : listes `wishlists` / `wishlist_items`, favoris d'avant copiés dans « Favoris » ; ⋮ à droite (la gauche
  porte le retour) ; « Récemment consultés » : 50 offres, 10 à l'accueil, croix sans confirmation.
- Détail : décisions `2026-11-27-*` du registre et rapports `.ia/runs/nuit-2026-11-27/`.

## Ce qui a été fait
- Lot A : web `a1c30a9` ; mobile (connexion, invitation, menu côté app).
- Lot B : menu `5c13a99` ; formulaires `653a97b`, `15d8185`, `6c15f5c`, `93c699c` ; masquage, vérification,
  confidentialité `1097118`, `fc97d96`, `4782e7f` (mobile `d0474c8`, `778d90a`, `c817da5`) ; numéros `382450a`.
- Lot C : web `1b859a3`, `65a98ab` ; mobile `e12e372`, `e218784`.
- Preuves : web 1008 tests verts, Pint propre, migrations jouées ; mobile tsc propre, 74 suites / 470 tests verts ;
  qa-check GO (`.ia/runs/nuit-2026-11-27/qa-check.md`).

## Ce que je n'ai pas vérifié
- Le rendu dans l'app (aucun écran vu dans le simulateur), Android, un appareil réel.
- L'envoi réel des SMS et des e-mails (codes, « Mes données »).
- Le retour arrière des migrations sur la base locale (vérifié à blanc seulement).
- Le contrat OpenAPI n'est pas régénéré.

## Ce qui reste en suspens
- WhatsApp : choisir un fournisseur et ses identifiants.
- Supprimer la table `offer_favorites` (plus lue).
- Appliquer « Rappels », « Offres », « Nouveautés » et le push quand ces envois existeront.
- Étendre la visibilité du profil au nom affiché ailleurs (avis, notifications, partenaires), si voulu.
- Contact d'urgence : la liste des langues refuse l'allemand (`config('form.locales')`).
- Après « Rendre principal », relire `/me` pour que le profil affiche le nouveau numéro.
- Fusionner `MenuShell` (copie dans `ProfileListScreen`) avec `ProfileScreenShell`.
- `UserResource` sert encore `gender` (inutilisé par l'app).
