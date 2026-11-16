# Contrat — Pays, régions, téléphone, connexion et changements vérifiés par code

- **Chantier** : `2026-11-17-pays-telephone-codes`
- **Demande** (chat, 2026-11-16, 17 points) :
  - genre en liste déroulante ;
  - e-mail facultatif ;
  - pays (modèle, ressource, indicatif, actif ou non, seed de tous les pays dans leur
    langue) et régions rattachées à un pays ;
  - pays, région et téléphone sur l'utilisateur ;
  - connexion par e-mail ou téléphone, avec un code envoyé ;
  - statut en ligne ;
  - changer de numéro ou d'e-mail avec un code ;
  - propriété `readonly` des champs.

  Complément : « quand un utilisateur crée un compte, on saisit l'email et/ou numéro de
  téléphone ».
- **Arbitrages tranchés** (chat) :
  - la connexion garde le **mot de passe et y ajoute le code** ;
  - les SMS sont écrits **dans le journal** pour l'instant (fournisseur réel plus tard) ;
  - dépendance **`propaganistas/laravel-phone`** ;
  - un e-mail ou un téléphone se saisit pour la première fois par un lien
    **« Ajouter » avec code**, jamais sans vérification.
- **Choix appliqués par défaut** (questions restées sans réponse, **à confirmer ici**) :
  - dans le champ téléphone, **on choisit un pays**, prérempli avec le pays du profil,
    et son indicatif s'en déduit (précision de l'humain : « on se base sur pays pour
    déterminer l'indicatif ») ;
  - seed des 26 cantons suisses ;
  - noms de pays natifs, plus le français et l'anglais ;
  - statut en ligne servi par l'API et affiché dans la liste Filament.
- **Statut** : validé le 2026-11-16 (décision `2026-11-17-contrat-pays-telephone-codes.md`)

## Ce que j'ai compris

### Lecture de quelques points

- Au point 14, « nouvel e-mail (type phone) » est lu comme **type `email`**.
- Les points « 14 et 15 » du point 16 sont lus comme **13 et 14**.
- **« Créer un compte »** est lu comme une **inscription depuis l'app**, qui n'existe pas
  encore.

### Partie A — Données : pays, régions, utilisateur, champs

- **Genre** : passe des boutons radio à la **liste déroulante** (`select`).
- **E-mail facultatif** :
  - la colonne `email` accepte `null` (migration) et reste unique ;
  - **au moins un des deux, e-mail ou téléphone**, est exigé partout où un compte est
    créé (app, Filament, `auth:create-user`) ;
  - le back-office Filament se connecte toujours par **e-mail et mot de passe**, sans
    code : un administrateur doit donc avoir un e-mail.
- **`Country`** (module `Location`, nouveau) :
  - champs : code ISO-2, nom (traduisible : natif + fr + en), **indicatif
    téléphonique** (`dial_code`, ex. `+41`, modifiable dans Filament), actif ;
  - ressource Filament : liste filtrable, actif modifiable, fiche ;
  - seed de **tous les pays**, environ 245 :
    - nom natif tiré de l'extension intl (« Deutschland », « España », « 日本 ») ;
    - noms fr et en ;
    - indicatif tiré de libphonenumber ;
  - tous les pays sont actifs au départ.
- **`Region`** (module `Location`) :
  - nom traduisible, pays (obligatoire), active ;
  - ressource Filament ;
  - seed des **26 cantons suisses**.
- **`User`** :
  - `country_id` et `region_id`, facultatifs ; la région doit appartenir au pays ;
  - `phone` : facultatif, unique, enregistré au format international E.164
    (`+41791234567`), avec `phone_country_id` (le pays de son indicatif) ;
  - `phone_verified_at` ;
  - `last_seen_at`, mis à jour au plus une fois par minute à chaque appel de l'app.
    L'utilisateur est **en ligne** s'il a été vu dans les 5 dernières minutes.
- **`UserResource`** expose : `country` (code, nom, indicatif), `region`, `phone`,
  `email` (peut être `null`), `last_seen_at` et `is_online`. La liste Filament affiche
  une pastille en ligne.
- **Nouvelles capacités des champs** (module `Form` et composants mobiles) :
  - **`readonly`** : booléen, `false` par défaut. Le champ est affiché, non modifiable,
    et jamais envoyé ni validé.
  - **Liste dépendante** : un `select` peut dépendre d'un autre champ (`depends_on`).
    Chaque option porte son parent, et l'app ne propose que les régions du pays
    choisi. Changer de pays vide la région.
  - **Type `phone`** : sur une ligne, un **sélecteur de pays** et le numéro.
    - Le sélecteur liste les pays actifs, chacun avec son indicatif, et il est prérempli
      avec le pays du profil.
    - **L'indicatif est celui du pays choisi** (`dial_code` du `Country`).
    - Le champ envoie `<champ>` (numéro) et `<champ>_country` (le pays choisi).
    - Le numéro est validé selon ce pays, puis enregistré en E.164.
    - Le pays du téléphone est mémorisé (`phone_country_id`) pour réafficher l'indicatif.
  - **Type `code`** : saisie d'un code à 6 chiffres (clavier numérique, remplissage
    automatique du code reçu par SMS sur iOS).
- **Informations personnelles** :
  - prénom, nom, genre (`select`, obligatoire) ;
  - date de naissance ;
  - **pays** (`select` des pays actifs, facultatif) ;
  - **région** (`select` dépendant du pays, facultatif).

### Partie B — Codes : inscription, connexion, changements

- **Codes** :
  - 6 chiffres, valables 10 minutes, 5 essais au plus ;
  - renvoi possible après 60 secondes ;
  - enregistrés **hachés** (table `verification_codes` : destination, motif, échéance,
    essais).
  - Un service unique, `VerificationCodeService`, les crée et les vérifie.
  - Envoi :
    - **e-mail** : mail Laravel, traduit (Mailpit en local) ;
    - **SMS** : `SmsSender` interchangeable, implémentation `LogSmsSender` qui écrit
      dans le journal.
- **Inscription depuis l'app** (nouvel écran « Créer un compte », lien depuis la
  connexion) :
  - pseudonyme, prénom, nom ;
  - **e-mail et/ou téléphone** (au moins un) ;
  - mot de passe et confirmation.

  Après l'envoi, un code part vers **chacun** des identifiants saisis, et l'écran
  demande le ou les codes. Le compte est créé avec ses identifiants vérifiés
  seulement quand tous les codes sont validés. L'app est alors connectée.
- **Connexion**, en deux étapes :
  1. **e-mail ou téléphone** (un seul champ, reconnu à la saisie) et mot de passe. Si
     les identifiants sont justes, un code part vers cet e-mail ou ce téléphone.
     L'API renvoie un jeton d'étape, pas encore l'accès.
  2. **Formulaire de code** : c'est seulement une fois le code validé que l'API rend
     le jeton de session et que l'app entre.
- **Profil**, deux liens :
  - **e-mail** : « Ajouter une adresse e-mail » si vide, « Changer d'adresse e-mail »
    sinon ;
  - **téléphone** : « Ajouter un numéro » si vide, « Changer de numéro » sinon.

  Chaque formulaire montre la valeur actuelle en **lecture seule** et le nouveau champ :
  - le numéro actuel s'affiche avec son indicatif, sur une ligne, en type `phone` en
    lecture seule ;
  - l'e-mail actuel s'affiche en type `email` en lecture seule.

  Un code part vers la **nouvelle** adresse ou le **nouveau** numéro. La donnée ne
  change qu'une fois le code validé (formulaire de code).
- **Contrôleurs et services** : un par formulaire, comme le profil (inscription,
  connexion, code de connexion, changement d'e-mail, changement de téléphone,
  validation de code). Services mobiles : une méthode par appel API.

## Découpage

1. **A web** → qa-check → commit. **A mobile** → qa-check → commit. Vérification dans
   le simulateur (compte de test).
2. **B web** → qa-check → commit. **B mobile** → qa-check → commit. Vérification dans
   le simulateur (inscription, connexion avec code lu dans Mailpit et dans le journal,
   changements).

## Critères de succès

1. Le genre s'affiche en liste déroulante.
2. Un compte peut n'avoir que le téléphone ou que l'e-mail, jamais aucun des deux.
3. Le back-office liste les pays seedés avec leur nom natif, fr et en, et leur
   indicatif. En désactiver un le retire des listes de l'app.
4. Les régions se gèrent dans Filament, et les 26 cantons sont présents. Dans
   Informations personnelles, la liste des régions ne montre que celles du pays choisi.
   Une région d'un autre pays est refusée par l'API.
5. Un numéro invalide pour le pays choisi est refusé avec un message traduit. Un
   numéro valide est enregistré en E.164.
6. **Inscription** : avec e-mail seul, téléphone seul ou les deux, le compte n'existe
   qu'après validation des codes, et l'app est alors connectée.
7. **Connexion** :
   - un mot de passe juste ne donne pas l'accès sans le code ;
   - un code faux, expiré ou épuisé est refusé ;
   - le code arrive à l'e-mail (Mailpit) ou au téléphone (journal) selon l'identifiant
     saisi.
8. **Changer d'e-mail ou de numéro** : la valeur actuelle s'affiche en lecture seule, le
   code part vers la nouvelle valeur, et rien ne change avant sa validation.
9. Un champ `readonly` n'est jamais modifiable dans l'app, ni pris en compte par l'API.
10. `is_online` vaut vrai après un appel de l'app, faux après 5 minutes sans appel. La
    pastille s'affiche dans la liste Filament.
11. Tous les textes viennent de l'API, en fr et en en.
12. Tests web et mobile, typage, lint et Pint au vert ; migrations à jour ;
    `openapi.json` régénéré.

## Hors périmètre

- Envoi réel de SMS (fournisseur à choisir) ;
- mot de passe oublié ;
- connexion Filament par code ;
- régions des autres pays que la Suisse (à créer dans Filament) ;
- afficher le statut en ligne dans l'app.

## Ce que j'ai décidé seul

- **Connexion par téléphone : format international obligatoire** (`+41 79 …` ou
  `0041 …`). Sans pays choisi, un numéro national est ambigu ; l'aide du champ le dit.
- **Seul un téléphone vérifié permet de se connecter.** Un numéro saisi dans Filament
  sans vérification ne suffit pas.
- **Erreur de connexion identique** pour un identifiant inconnu et un mot de passe faux
  (`auth.failed`, sur `identifier`).
- **Un défi par opération (UUID), un code par canal.** L'inscription avec e-mail et
  téléphone demande l'e-mail puis le SMS ; l'API rend le prochain code à saisir (202).
- **Validation du code : une route publique unique** (`POST /auth/codes/verify`), qui
  aiguille selon l'opération. Un défi de changement n'est validable que par le compte
  qui l'a ouvert (jeton lu s'il est présent). Renvoi : `POST /auth/codes/resend`.
- **Réponses** : 202 et le défi tant qu'un code est attendu ; 201 avec le compte et
  le jeton à la fin d'une inscription ; 200 sinon.
- **Limites de fréquence** : connexion 5/min par identifiant et adresse IP ;
  inscription 5/min par IP ; codes, changements et renvoi 10/min par IP.
- **Unicité revérifiée à la validation du code** (après le qa-check) : une valeur prise
  entre-temps par un autre compte est refusée proprement, pas par l'index unique.
- **Le sélecteur de pays du téléphone n'est pas prérempli à l'inscription** : un
  invité n'a pas de pays de profil.
- Données de l'inscription en attente : chiffrées en base (`payload` chiffré), mot de
  passe déjà haché.

## Ce qui a été fait

- **Partie A** — web `1247431`, mobile `9a05c86` : pays et régions (module
  `Location`), téléphone, statut en ligne, champs `readonly`, listes dépendantes, types
  `phone` et `code`. qa-check GO (`04-qa-check-partie-a.md`).
- **Partie B, web** (`69fc8e4`) :
  - table `verification_codes` et `VerificationCodeService` (6 chiffres hachés,
    10 min, 5 essais, renvoi après 60 s) ;
  - e-mail traduit, SMS par `SmsSender` → `LogSmsSender` ;
  - contrôleurs et services Login, Register, EmailChange, PhoneChange, Verification ;
  - formulaires `auth.register`, `auth.code`, `auth.profile.email`,
    `auth.profile.phone` ;
  - 164 tests, `openapi.json` régénéré.
- **Partie B, mobile** (`e932262`) : `ChallengeFlow` (formulaire, puis code, renvoi,
  retour) ; écrans connexion, Créer un compte, Ajouter/Changer l'e-mail et le numéro ;
  un service par appel ; 69 tests.
- **Simulateur iOS**, compte de test créé puis supprimé :
  - inscription avec e-mail et téléphone, code e-mail lu dans Mailpit (dans la langue
    de l'app), puis code SMS lu dans le journal ;
  - le compte n'existe pas entre les deux codes ;
  - changement de numéro : valeur actuelle en lecture seule avec son indicatif,
    inchangée en base avant le code, puis confirmation ;
  - connexion par téléphone : mot de passe seul → écran de code ; code faux refusé
    (« Incorrect code. ») ; bon code → accueil.
- **qa-check partie B** : GO, 0 bloquant (`qa-partie-b.md`).

## Ce que je n'ai pas vérifié

- **Changement d'e-mail dans le simulateur** : couvert par les tests API seulement
  (formulaire affiché, pas envoyé).
- **Renvoi du code et code expiré dans l'app** : couverts par les tests seulement.
- **Android** et **remplissage automatique du code SMS** sur un vrai iPhone.
- **Limites de fréquence de l'inscription et des codes** : déclarées, sans test du 429
  (seule la connexion en a un).
- **Essais concurrents** : `attempts` s'incrémente sans verrou ; quelques essais de
  plus restent possibles en parallèle, bornés par la limite de fréquence.

## Ce qui reste en suspens

- **Fournisseur SMS réel** à choisir (hors périmètre). À ce moment, prévoir une
  protection contre l'envoi massif vers des numéros quelconques depuis l'inscription.
- **Ta session dans le simulateur** a été déconnectée pour les essais : il faut te
  reconnecter, désormais avec mot de passe puis code reçu par e-mail (Mailpit).
- **Deux liens « Retour »** sur l'écran de code d'un changement (celui du profil et
  celui de l'étape) : fonctionnels, mais redondants.
- **Mot de passe oublié** : hors périmètre, encore absent.
