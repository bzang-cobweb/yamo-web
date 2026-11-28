# `2026-11-28` — Serveur : identifiant, code, mot de passe, puis inscription

- **Type** : décision
- **Remplace en partie** : 2026-11-27-connexion-sans-mot-de-passe (le mot de passe revient ; l'inconnu reçoit un code ;
  l'inscription vient après le code et le mot de passe)
- **Question posée** : contrat `specs/2026-11-28-connexion-panneau.md`, section « API », partie serveur.
- **Ce qui est retenu** :
  - `POST /auth/login` répond **toujours 202** `{challenge, channel, destination}` et envoie le code : compte connu →
    défi `LOGIN` ; inconnu, ou compte inactif par SMS (numéro recyclé) → défi `REGISTER` sans compte, l'identifiant dans
    son `payload` (`email`, ou `phone` E.164 et `phone_country_id`). Un numéro jamais vérifié d'un compte actif se
    connecte toujours (et devient vérifié) ;
  - `POST /auth/codes/verify` (`LOGIN`/`REGISTER`) → `{next: 'password', challenge, mode}` (`sign-in`, `create`,
    `register`), défi laissé ouvert et marqué vérifié ; pose `email_verified_at`/`phone_verified_at` de l'identifiant
    prouvé ; compte en attente de suppression : `next: 'restore'` inchangé ;
  - `POST /auth/session` (`throttle:auth-login`, clé : le défi) : défi vérifié, non clos, de moins de
    `auth.verification_codes.password_minutes` (30) minutes depuis la validation du code. `sign-in` → mot de passe
    juste : `{data, token}`, défi clos ; faux : 422 `password`, essais comptés sur le défi, clos au cinquième ;
    `reset: true` ou `create` → nouveau mot de passe confirmé (`Password::defaults()`) ; `register` → mot de passe
    haché gardé dans le `payload` (chiffré), `{next: 'register', challenge}` ;
  - formulaires `auth.session.password`, `auth.session.new-password` ; `auth.register?channel=email|phone` : prénom,
    nom, puis l'autre contact facultatif (téléphone + pays, ou e-mail), unique ;
  - `POST /auth/register` : défi `REGISTER` vérifié avec mot de passe → compte (identifiant vérifié, autre contact non
    vérifié, pseudonyme généré, langue de la requête, rôle membre), 201 `{data, token}`, défi clos ;
  - tous les champs téléphone de l'API proposent tous les pays (`PhoneNumbers::countryOptions()` et `country()` ne
    filtrent plus les actifs) ; le défaut reste le pays courant.
- **Décidé seul (mode nuit)** :
  - les refus liés au défi (inconnu, déjà servi, non vérifié, expiré, épuisé, sans mot de passe) portent sur `code`,
    comme `codes/verify`, `password/reset` et `account/restore` ; seul le mot de passe faux porte sur `password` ;
  - le compteur `attempts` du code est remis à zéro à la validation et sert ensuite aux mots de passe faux ;
  - « Mot de passe oublié » (`reset`) déconnecte les autres appareils, comme le mot de passe oublié d'hier ; `create`
    ne déconnecte personne ;
  - pseudonyme : prénom + nom en ASCII minuscule sans signe (30 caractères au plus), sinon `membre` ; s'il est pris
    (comptes supprimés compris), suffixe 2, 3… ;
  - un identifiant d'inscription pris entre l'envoi et la validation du code (autre compte, compte supprimé, numéro
    supplémentaire d'un compte actif) est refusé dès le code (422 `code`) et le défi est clos ;
  - avec un âge minimum réglé (décision 2026-11-19-age-minimum), `auth.register` demande aussi `birth_date` (la même
    que le profil) ; sans réglage, le formulaire est exactement celui du contrat ;
  - `?channel=sms` vaut `phone` ; sans `channel`, ni l'un ni l'autre contact ;
  - le mot de passe oublié d'hier (`auth.password.forgot`, `auth.password.reset`, `POST /auth/password/forgot|reset`)
    reste en place, sans usage dans l'app ;
  - l'ancien `RegisterService::start/complete` (inscription par codes) est retiré : le défi `REGISTER` naît seulement
    de `POST /auth/login`.
- **Risque assumé** : l'autre contact de l'inscription n'est pas vérifié. Un numéro saisi ainsi (non vérifié) mène,
  comme un numéro saisi au back-office, à ce compte quand quelqu'un se connecte par SMS avec ce numéro (règle gardée du
  2026-11-27) : la personne qui possède vraiment le numéro peut alors entrer dans ce compte (code + « Mot de passe
  oublié ? ») et ne peut pas s'inscrire avec lui. À trancher : ne plus laisser un numéro non vérifié servir à la
  connexion, ou ne plus le compter comme pris.
- **Portée** : module Auth (services `LoginService`, `SessionService`, `RegisterService`, `Challenges`, formulaires,
  routes, `PhoneNumbers`, `Pseudonyms`, textes `mobile.php`), `RouteGuardTest`.
- **Tranché par** : contrat (humain absent), session de nuit pour le détail
