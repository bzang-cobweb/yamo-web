# Partenaires : validation, notifications, « Devenir partenaire »

Validation : non — exécuté en mode nuit le 2026-11-24

## Demande (telle que reçue)

1. La modification ou l'ajout d'un partenaire le met en mode en validation (notifications envoyées à tous les
   autres admin(edition) et à l'utilisateur qui a effectué l'opération).
2. L'utilisateur back qui a les droits valide et met le partenaire en statut accepté (notifications envoyées à
   tous les admins).
3. Mettre un bouton (barre bottom fixe) « devenir partenaire » sur la page mes partenaires pour tous les
   utilisateurs : connecté → formulaire « ajouter un partenaire » ; pas connecté → login.
4. Pages notifications : retrouver toutes les notifications générées aux actions sur des partenaires, trier par
   status.

## Ce que j'ai compris

- **Administrateurs (édition)** : les comptes rattachés au partenaire (`partner_user`) ; tous peuvent le modifier
  (`PartnerPolicy::update`, `isAdministeredBy`), `is_admin` ne sert qu'à le retirer.
- **1.** L'ajout et la modification depuis l'app mettent déjà le partenaire en validation. Il manque les
  notifications : à **chaque** envoi (ajout ou modification, même déjà en validation), tous les comptes rattachés
  sont prévenus, l'auteur compris, avec un texte pour l'auteur (« Votre partenaire … est en validation ») et un
  autre pour les autres (« … modifié par Prénom Nom, en validation »). La notification « statut changé » ne part
  plus pour le passage en validation (sinon deux notifications pour la même action).
- **2.** Au back-office, une action **« Valider »** sur un partenaire en validation, pour qui peut le modifier,
  le passe en « Publié » (le statut accepté du projet). Les comptes rattachés sont prévenus (« Partenaire
  accepté »), comme aujourd'hui pour un changement de statut.
- **3.** Un visiteur n'a aucun accès à « Mes partenaires » (liste du profil, connectée). Le bouton « Devenir
  partenaire », dans la barre fixe en bas, va donc :
  - sur « Mes partenaires » (à la place d'« Ajouter un partenaire ») : ouvre le formulaire ;
  - sur la page publique « Partenaires », que voient aussi les visiteurs : connecté, le formulaire ; sinon, la
    connexion.
  Le bouton est décrit par l'API (le `footer` des listes) ; l'app sait ouvrir la connexion depuis un bouton de liste.
- **4.** Dans « Notifications », un type « Partenaire » dans le filtre « Type », et pour ce type un filtre
  « Statut du partenaire » (en validation, publié, refusé, archivé : le statut que la notification annonce) ;
  chaque carte porte la pastille de ce statut. « Trier par status » est compris comme filtrer par statut, le tri
  de la liste restant la date (comme les demandes de logement).

## Découpage

1. Notifications d'envoi (événement, listener, service, notification) ; ne plus doubler en validation.
2. Action « Valider » au back-office ; texte « accepté ».
3. Bouton « Devenir partenaire » : `footer` des deux listes ; ouverture de la connexion dans l'app.
4. Filtre « Partenaire » des notifications (`NotificationFilter` du module Partner).

## Critères de succès

- Ajouter ou modifier un partenaire depuis l'app : il est en validation ; l'auteur et chaque autre compte rattaché
  reçoivent une notification, chacun son texte ; aucune notification « statut changé » en plus.
- Au back-office, « Valider » n'apparaît que sur un partenaire en validation, pour qui peut le modifier ; il le
  publie ; les comptes rattachés reçoivent « Partenaire accepté ».
- « Mes partenaires » et « Partenaires » ont une barre fixe « Devenir partenaire » : connecté, elle ouvre le
  formulaire d'ajout ; visiteur (page « Partenaires »), la connexion.
- Dans « Notifications », le type « Partenaire » ne montre que les notifications des partenaires ; le filtre
  « Statut du partenaire » les restreint à un statut ; chaque carte montre sa pastille.
- Tous les tests passent (web et app), le formatage aussi.
