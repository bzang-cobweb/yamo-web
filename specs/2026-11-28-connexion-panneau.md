# Contrat — Se connecter ou s'inscrire dans un panneau : code, puis mot de passe, puis inscription

Validation : non — exécuté en mode nuit le 2026-11-28

## Demande

`specs/todo/2026-11-28-yamo-connexion-panneau.md` (copie de `~/Downloads/yamo.rtf`, version du 2026-11-28).

## Ce que j'ai compris

Le parcours de connexion change d'ordre : **identifiant → code → mot de passe → (inscription)**. Le mot de passe
revient (décision 2026-11-27-connexion-sans-mot-de-passe remplacée en partie). Tout se passe dans un panneau à sa
hauteur maximale, titré « Se connecter ou s'inscrire », ouvert depuis le bouton des onglets d'un visiteur. L'app se
souvient du dernier moyen de connexion (« cookie » = stockage sécurisé de l'appareil) pour proposer « Bon retour ».

## Interprétations retenues (décidé seul)

- « cookie » = `expo-secure-store` sur l'appareil (déjà une dépendance) : après une connexion réussie, l'app garde le
  canal utilisé, l'e-mail et le numéro (et son pays) du compte, leurs versions masquées, le prénom et le nom. « Ce
  n'est pas vous ? » les efface.
- « Try another way » : si l'app connaît l'autre identifiant (mémorisé), elle redemande un code avec lui
  (`POST /auth/login`) et dit « code envoyé à <masqué> » ; sinon elle revient au formulaire de connexion, l'autre
  canal présélectionné.
- Le code part toujours, compte connu ou non : l'existence du compte n'est révélée qu'après le code (mieux qu'hier,
  où l'identifiant inconnu le disait tout de suite).
- Un compte existant sans mot de passe (inscrit le 2026-11-27) crée le sien à cette étape (mot de passe + confirmation).
- « Mot de passe oublié » à l'étape du mot de passe : le code vient d'être validé, la personne a prouvé son identifiant ;
  elle choisit directement un nouveau mot de passe (mot de passe + confirmation), puis elle est connectée.
- Inscription : « nom, prénom » + l'autre moyen de contact, facultatif (téléphone si on s'est identifié par e-mail,
  e-mail si par téléphone — le texte dit « téléphone » deux fois). Le pseudonyme (`name`, unique) est généré par le
  serveur à partir du prénom et du nom. La date de naissance n'est plus demandée à l'inscription (reste au profil).
- Indicatifs : tous les champs téléphone proposent **tous les pays** (indicatif), pas seulement les actifs ; défaut :
  le dernier indicatif mémorisé par l'app, sinon le pays courant.
- Onglets d'un visiteur : un seul composant (`GuestTab`, déjà là) : titre, sous-titre, message, bouton.

## API (contrat entre le serveur et l'app)

1. `GET /api/v1/forms/auth.login` : `channel` (segmented, si les deux canaux), `email`, `phone` (+ `phone_country`,
   options : tous les pays avec leur indicatif ; défaut : pays courant).
2. `POST /api/v1/auth/login` `{channel?, email | phone + phone_country}` → **toujours 202**
   `{challenge, channel: 'email'|'sms', destination}` (destination masquée). Compte connu : défi `LOGIN` ; inconnu (ou
   compte inactif par SMS, numéro recyclé) : défi `REGISTER`, sans compte, l'identifiant dans son `payload`. Même
   forme de réponse dans les deux cas. 422 si l'identifiant est invalide.
3. `POST /api/v1/auth/codes/verify` `{challenge, code, device_name}` pour `LOGIN`/`REGISTER` → 200
   `{next: 'password', challenge, mode}` avec `mode` : `sign-in` (compte avec mot de passe), `create` (compte sans
   mot de passe), `register` (pas de compte). Le défi reste ouvert, marqué vérifié. Pour un compte existant, pose
   `email_verified_at` ou `phone_verified_at` s'ils manquaient. Compte en attente de suppression : inchangé
   (`next: 'restore'`).
4. Formulaires de l'étape du mot de passe, servis comme les autres :
   - `auth.session.password` : `password` ; titre « Votre mot de passe », bouton « Se connecter » ;
   - `auth.session.new-password` : `password`, `password_confirmation` (règles `Password::defaults()`) ; titre
     « Choisissez un mot de passe », bouton « Continuer ».
5. `POST /api/v1/auth/session` `{challenge, password, password_confirmation?, reset?: bool, device_name}`
   (`throttle:auth-login`) : le défi doit être vérifié, non clos, de moins de 30 minutes depuis sa vérification.
   - `sign-in`, sans `reset` : mot de passe juste → 200 `{data: User, token}` (défi clos) ; faux → 422 sur `password` ;
   - `sign-in` avec `reset: true`, ou `create` : nouveau mot de passe confirmé → enregistré → 200 `{data, token}` ;
   - `register` : mot de passe confirmé → gardé haché dans le défi → 200 `{next: 'register', challenge}`.
6. `GET /api/v1/forms/auth.register?channel=email|phone` : `first_name` (Prénom), `last_name` (Nom), puis l'autre
   contact facultatif : `phone` (+ `phone_country`, tous les pays) si `channel=email`, `email` si `channel=phone`.
   Titre « Créer votre compte », bouton « Créer mon compte ».
7. `POST /api/v1/auth/register` `{challenge, first_name, last_name, email? | phone? + phone_country?, device_name}` :
   défi `REGISTER` vérifié avec mot de passe → compte créé (identifiant vérifié, autre contact non vérifié et unique,
   pseudonyme généré, langue, rôle membre) → 201 `{data: User, token}`.
8. `POST /api/v1/auth/codes/resend` `{challenge}` : inchangé.
9. Textes de l'app (`Modules/Auth/lang/{fr,en,de}/mobile.php`, section `auth`) :
   `signInOrUp` (existe, aussi titre du panneau), `guest.profile` (« Connectez-vous pour gérer vos réservations et vos
   logements. »), `guest.notifications` (existe), `guest.profileSubtitle`, `guest.notificationsSubtitle`,
   `welcomeBack` (« Bon retour, :name »), `welcomeNote.email` / `welcomeNote.sms` (« Nous allons vous envoyer un code
   par e-mail / SMS pour vous connecter. »), `notYou` (« Ce n'est pas vous ? »), `codeSentTo` (« Nous avons envoyé un
   code à :destination. »), `didntGetIt` (« Vous ne l'avez pas reçu ? »), `sendNewCode` (« Envoyer un nouveau code »),
   `tryAnotherWay` (« Essayer autrement »), `codeBySms` (« Recevoir un code par SMS »), `codeByEmail` (« Recevoir un
   code par e-mail »), `forgotPassword` (« Mot de passe oublié ? »).

## Découpage

- **Serveur** (sous-agent, dépôt web) : points 1 à 9, tests, décisions.
- **App** (sous-agent, dépôt mobile, en parallèle, API simulée dans les tests) : `GuestTab` (titre, sous-titre,
  message, bouton) ; panneau `BottomSheet` hauteur maximale titré ; parcours : « Bon retour » ou formulaire, code
  (envoi automatique à 6 chiffres, « Vous ne l'avez pas reçu ? Envoyer un nouveau code », « Essayer autrement » en
  panneau à la hauteur du contenu), mot de passe (et « Mot de passe oublié ? »), inscription ; mémoire du dernier moyen ;
  indicatif mémorisé ; les autres entrées vers `/sign-in` ouvrent le même parcours.
- **Intégration** (moi) : tests des deux côtés, vérification croisée de l'API, qa-check.

## Critères de succès
- Un visiteur touche « Se connecter ou s'inscrire » : un panneau à hauteur maximale s'ouvre, titré ainsi.
- Avec un dernier moyen mémorisé : « Bon retour, Prénom Nom », l'identifiant masqué, la note, « Se connecter » envoie
  le code, « Ce n'est pas vous ? » revient au formulaire ; sans : le formulaire e-mail / téléphone.
- Étape du code : « Nous avons envoyé un code à <masqué> », envoi dès 6 chiffres, nouveau code, « Essayer autrement ».
- Après le code : mot de passe (compte existant, avec « Mot de passe oublié ? »), ou mot de passe + confirmation
  (nouveau compte), puis connecté, ou inscription (prénom, nom, l'autre contact facultatif) puis connecté.
- Les champs téléphone proposent tous les pays ; l'identifiant validé par code est marqué vérifié.
- Web : tests verts, Pint propre ; mobile : tsc sans erreur, Jest vert (« Test Suites: » compris).
