# Espace pays : choisir son pays avant d'entrer, devise par pays

Demande du 2026-11-18 :

> la notion de pays est très forte, alors je veux donner l'impression de changer d'app quand on change de pays. Au départ, il faut donc sélectionner le pays avant d'entrer clairement dans l'app

Réponses aux questions posées :

- un seul compte pour tous les pays ;
- pas d'écran de choix tant qu'un seul pays est ouvert ;
- apparence : le **drapeau seul** ;
- devise par pays : oui, dans ce chantier.

## Ce que j'ai compris

L'app reste unique. Le **pays choisi** est l'espace dans lequel on se trouve : on le choisit en entrant, il est rappelé par le drapeau, et ce que l'on voit et crée appartient à ce pays. Le compte, lui, est le même partout : changer de pays ne déconnecte pas.

Le Cameroun est le premier pays : `project:init --country=CM`.

### Serveur

- **Devise par pays.**
  - `countries` gagne `currency` (code ISO 4217), rempli par le seeder depuis les données ICU (Cameroun → XAF) et modifiable dans le back-office.
  - La devise d'une offre est celle de son pays, et non plus celle du domaine (`Domain::currency()` disparaît).
  - L'affichage suit la devise : `1 850 FCFA`, sans décimales.
- **Liste publique des pays**, avant toute connexion : `GET /api/v1/countries`.
  - Elle sert les pays actifs, avec code, nom traduit, nom natif, drapeau (emoji tiré du code), devise et indicatif.
  - Elle est triée par nom.
- **Pays courant.**
  - L'app envoie `X-Country: CM` sur chaque requête.
  - Le serveur le résout ainsi :
    - un pays actif valide est retenu ;
    - si l'en-tête manque ou est invalide alors qu'un seul pays est actif, c'est ce pays ;
    - sinon, aucun pays.
  - Ce qui dépend du pays répond alors 422, avec un message « Choisissez un pays ».
  - Ce qui ne dépend pas du pays (connexion, profil, langues, textes) n'est pas touché.
- **Ce qui appartient à un pays :**
  - **Offres.**
    - `offers` gagne `country_id`.
    - « Mes logements » ne montre que les offres du pays courant.
    - Une offre créée l'est dans le pays courant, avec sa devise.
    - Le choix d'adresse ne propose que les adresses du compte situées dans ce pays.
    - Modifier ou retirer une offre d'un autre pays répond 404, comme pour une offre d'autrui.
  - **Adresses du compte.**
    - « Mes adresses » ne montre que celles du pays courant.
    - Le formulaire fixe le pays sur le pays courant, verrouillé.
    - Une adresse d'un autre pays répond 404 dans cet espace.
  - **Inscription et changement de numéro** : le pays du téléphone est proposé d'office sur le pays courant, et reste modifiable si plusieurs pays sont actifs.
- **Données existantes.** Une migration range chaque offre dans le pays de son adresse ; sans adresse, dans le seul pays actif s'il n'y en a qu'un ; sinon le pays reste vide. Une offre sans pays n'apparaît dans aucun espace de l'app, mais reste visible dans le back-office.
- **Back-office.**
  - Le pays et sa devise sont visibles et filtrables sur les offres.
  - La devise est modifiable sur la fiche d'un pays.
  - Le back-office voit tous les pays : ce n'est pas un espace.

### App mobile

- **Entrée.** Avant l'accueil et avant la connexion, l'app charge les pays actifs.
  - Un seul pays : on y entre directement, sans écran de choix.
  - Plusieurs pays : un **écran de choix plein écran** propose drapeau et nom pour chacun. On n'entre dans l'app qu'après avoir choisi.
- **Mémoire du choix.** Il est gardé sur l'appareil, comme la langue. Au prochain lancement, on entre directement dans ce pays, s'il est toujours actif. Un pays fermé entre-temps ramène à l'écran de choix.
- **Rappel.** Le **drapeau** du pays courant est affiché en haut de l'app (barre du haut), seul.
  - Avec plusieurs pays actifs, le toucher rouvre l'écran de choix.
  - Avec un seul pays, il n'est pas cliquable.
- **Changer de pays** recharge toutes les données (listes, formulaires, menu) dans le nouvel espace. On reste connecté.
- Toutes les requêtes portent `X-Country`.

## Critères de succès

1. Avec le seul Cameroun actif, l'app entre directement. Les offres créées sont au Cameroun, en XAF, et s'affichent « 1 850 FCFA ».
2. Avec deux pays actifs :
   - le premier lancement montre l'écran de choix ;
   - le choix est gardé ;
   - le drapeau ramène à l'écran ;
   - changer de pays montre d'autres « Mes logements » et d'autres « Mes adresses », sans déconnexion.
3. Une requête sans pays alors que plusieurs pays sont actifs reçoit 422 sur ce qui dépend du pays. Une offre ou une adresse d'un autre pays répond 404.
4. `GET /api/v1/countries` est public et ne sert que les pays actifs, avec drapeau et devise.
5. La migration range les offres existantes sans perte ; le retour en arrière fonctionne.
6. Dans le back-office, la devise d'un pays se modifie, et les offres se filtrent par pays.
7. Tests :
   - web : les tests passent, Pint est propre, `openapi.json` est à jour ;
   - mobile : les tests passent (écran de choix, mémoire, drapeau, en-tête), tsc et le lint sont propres.

## Hors périmètre

- Un thème par pays (couleurs, logo) : non retenu, le drapeau suffit.
- Le paiement, et un fournisseur SMS propre à chaque pays.
- La consultation publique des offres par pays : chantier à venir, qui s'appuiera sur ce pays courant.
- La conversion d'une devise à l'autre.

## Ce que j'ai décidé seul

- **Pays courant.** `CurrentCountry` (module Location) lit `X-Country` une fois par requête. Ce qui dépend du pays appelle `CurrentCountry::require()` ; à défaut, la réponse est 422 « Choisissez un pays ». La connexion, le profil et les textes ne sont pas touchés.
- **Devise par pays.** Elle vient des données ICU : une migration et le seeder la remplissent, et le back-office la modifie. `Domain::currency()` est retiré.
- **Drapeau.** L'emoji est calculé à partir du code ISO, sans aucune image.
- **Formulaire d'adresse.** Le pays courant y est la seule option, donc le champ est verrouillé et une adresse d'un autre pays est refusée (422).
- **Offres dans le back-office.**
  - Le formulaire gagne un champ pays : verrouillé quand un seul pays est actif, sinon un changement filtre les adresses proposées.
  - L'adresse doit se trouver dans le pays de l'offre. La QA avait trouvé une adresse sénégalaise acceptée sur une offre camerounaise.
- **Un domaine à « un par compte »** compte désormais une offre par compte **et par pays**.
- **Dans l'app :**
  - un nouveau module `src/features/country` porte le fournisseur, l'écran d'entrée, le drapeau et les sélecteurs ;
  - le drapeau s'affiche aussi sur l'accueil d'un invité, à côté de la langue ;
  - entrer dans le même pays ne recharge rien ;
  - la liste des pays garde la précédente pendant un changement de langue. La QA avait trouvé que la sélection se perdait.
- **Tests.** Les factories d'adresse et d'offre prennent le pays actif déjà présent, et `phpunit` voit un seul pays par test.

## Ce qui a été fait

- **Web** :
  - `83f0b10` : serveur ;
  - `e1c00bd` et `cdc99af` : textes ;
  - `8c160c6` : adresse dans le pays de l'offre ;
  - `88a5b1a` : tests du back-office ;
  - décisions : `801b885`, puis amendements `2026-11-18-ecran-entree-toujours` et `2026-11-18-ecran-entree-selecteurs`.
- **Mobile** :
  - `6ef888e` : espace pays ;
  - `771f9a3` et `6b1d15d` : la langue garde le pays ;
  - `600e4ac` : sélecteurs, description, écran affiché une fois.
- **Contrôles** :
  - web : 378 tests passent, Pint est propre, `openapi.json` est à jour ;
  - mobile : 181 tests passent, `tsc` et le lint sont propres.
- **QA** (`bza-dev:qa-check`) :
  - premier verdict : NO-GO, un écart majeur (langue / pays) ;
  - après correctif : GO (`.ia/runs/2026-11-18-espace-pays/qa-check.md`).
  - La refonte de l'écran (`600e4ac`), demandée ensuite, a été vérifiée par les tests et dans le simulateur : sélecteur, recherche « came » → Cameroun, entrée, drapeau sur l'accueil, relance sans écran d'entrée. La QA ne l'a pas revue.

## Ce que je n'ai pas vérifié

- **Démontage de la navigation après connexion,** quand la langue du compte diffère de celle de l'appareil (signalé par la QA). Probablement réglé par le même correctif, mais non rejoué.
- **Le mode hors ligne de l'écran d'entrée,** sur un appareil.
- **L'app connectée** dans le simulateur (« Mes logements » par pays).

## Ce qui reste en suspens

- **En local, les 245 pays sont actifs.** Pour un espace « Cameroun » seul, il faut lancer `ddev artisan project:init --domain=rental --name=… --locale=fr --country=CM --channels=email,phone --no-interaction`. La commande modifie `.env` et désactive les autres pays : je ne l'ai pas lancée.
- **Rien n'est poussé.**
