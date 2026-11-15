# Contrat — Profil utilisateur, connexion API et initialisation mobile

- **Chantier** : `2026-11-15-profil-api-mobile`
- **Demande** (chat, 2026-11-15) :
  1. ajouter les champs `first_name`, `last_name` et `locale` (fr ou en selon
     `config/app.php`) et mettre à jour le modèle et la ressource ;
  2. écrire la connexion via une API à travers Sanctum (utilisée par le mobile) ;
  3. initialiser le projet mobile Expo.
- **Statut** : en attente de validation

## Ce que j'ai compris

### Étape 1 — Profil : prénom, nom, langue

- La table `users` reçoit `first_name`, `last_name` et `locale`, par une
  **nouvelle migration** (la migration des utilisateurs a déjà tourné).
- `locale` n'accepte que les langues de `config('app.supported_locales')`
  (aujourd'hui `fr`, `en`) ; valeur par défaut : `config('app.locale')` (`fr`).
- Le modèle `User` et la ressource Filament Utilisateurs (formulaire, table)
  exposent les trois champs, libellés traduits fr/en.
- **Question A — que devient `name` ?** (voir Arbitrages)

### Étape 2 — Connexion API (Sanctum, jetons)

Routes du module Auth, préfixe `/api/v1/auth` :

| Route | Rôle | Accès |
|---|---|---|
| `POST /login` | Reçoit `email`, `password`, `device_name` ; renvoie un jeton Sanctum et l'utilisateur | invité, limité à 5 essais/minute par e-mail + IP |
| `POST /logout` | Révoque le jeton utilisé par la requête | jeton valide |
| `GET /me` | Renvoie l'utilisateur connecté | jeton valide |

- Réponses par une `JsonResource` (`UserResource`) : `id`, `first_name`,
  `last_name`, `email`, `locale` — ni mot de passe ni rôles.
- Identifiants faux : 422 avec un message traduit, sans dire lequel des deux
  est faux.
- `GET /me` passe par la policy `view` du modèle (branche hors Filament :
  chacun voit son propre compte).
- Contrat `openapi.yaml` dérivé du code (`bza-laravel:generate-openapi`) —
  **voir Dépendances**.
- Hors périmètre : inscription, mot de passe oublié, modification du profil
  depuis l'app, notifications push (chantiers suivants).

### Étape 3 — Projet mobile Expo

- Dans `/Users/bertrandzang/home/private/yamo/mobile` (dépôt séparé), en
  conservant son `.git`, son `README.md` et son `.claude/settings.json`.
- `create-expo-app`, modèle par défaut (TypeScript, Expo Router), dernier SDK
  stable au moment de l'installation.
- URL de l'API dans une variable `EXPO_PUBLIC_API_URL` (`.env` local, non
  commité ; `.env.example` commité).
- **Question B — jusqu'où va l'initialisation ?** (voir Arbitrages)

## Découpage

1. Étape 1 (web) → vérification `qa-check` → commit.
2. Étape 2 (web) → vérification `qa-check` → commit.
3. Étape 3 (mobile) → vérification `qa-check` → commit dans `yamo-mobile`.

## Critères de succès

### Étape 1
- Un utilisateur peut être créé et modifié dans le back-office avec prénom,
  nom et langue ; prénom et nom sont obligatoires ; une langue hors `fr`/`en`
  est refusée.
- La liste des utilisateurs affiche prénom, nom et langue ; prénom et nom sont
  triables et cherchables.
- Les comptes existants restent connectables et gardent leur nom (repris selon
  la réponse à la question A).
- `php artisan migrate:status` montre la nouvelle migration `Ran`, en local et
  sous DDEV.
- Les tests existants passent, complétés pour les nouveaux champs.

### Étape 2
- Avec des identifiants valides, `POST /api/v1/auth/login` renvoie 200, un
  jeton utilisable, et l'utilisateur sans donnée sensible.
- Avec des identifiants faux : 422, aucun jeton créé.
- Au 6ᵉ essai en une minute : 429.
- Avec ce jeton, `GET /api/v1/auth/me` renvoie l'utilisateur ; sans jeton, 401.
- Après `POST /api/v1/auth/logout`, le même jeton renvoie 401.
- Chaque route de contrôleur porte une garde (`can:` ou motif listé) — test de
  balayage des routes.
- `openapi.yaml` décrit les trois routes et est régénérable par une commande.

### Étape 3
- `npx expo start` démarre sans erreur ; l'app s'ouvre dans le simulateur iOS.
- `npx tsc --noEmit` et `npx expo lint` passent.
- Aucun secret commité ; `EXPO_PUBLIC_API_URL` documentée dans `.env.example`
  et le README.
- (Selon la question B) depuis le simulateur, la connexion avec un compte de
  test renvoie l'utilisateur de l'API DDEV.

## Arbitrages

### A — Que devient la colonne `name` ?

1. **Constat** : `users.name` est obligatoire (migration `0001_01_01_000000`),
   utilisé par Filament pour afficher le compte (menu, `getFilamentName`) et
   demandé par `make:filament-user`. Un seul compte existe (le vôtre).
2. **Options** :
   - (a) supprimer `name` ; reprendre son contenu dans `first_name` /
     `last_name` (premier mot / reste) ; le nom affiché devient « prénom nom ».
   - (b) garder `name` comme nom d'affichage libre, en plus des deux champs.
3. **Recommandation** : (a) — une seule source pour l'identité, pas de
   désynchronisation. Conséquence : `make:filament-user` ne fonctionne plus ;
   le module fournit une commande `auth:create-user` (prénom, nom, e-mail, mot
   de passe, langue, option `--super-admin`).
4. **Défaut sans réponse** : (a).
5. **Bloqué en attendant** : l'étape 1. Rien d'autre ne démarre avant la
   validation du contrat.

### B — Jusqu'où va l'initialisation mobile ?

1. **Constat** : la demande dit « initialiser » ; l'étape 2 fournit une API de
   connexion que rien ne consomme encore.
2. **Options** :
   - (a) squelette seul : Expo Router, TypeScript, lint, variable d'URL d'API.
   - (b) squelette + écran de connexion branché sur l'API (jeton dans
     `expo-secure-store`, appels par `@tanstack/react-query`), écran d'accueil
     qui affiche l'utilisateur et un bouton de déconnexion.
3. **Recommandation** : (b) — prouve de bout en bout que l'étape 2 sert le
   mobile, et pose les briques (stockage du jeton, client API) dont tous les
   écrans suivants auront besoin.
4. **Défaut sans réponse** : (a), le périmètre littéral.
5. **Bloqué en attendant** : l'étape 3.

### C — Dépendances à autoriser

| Où | Paquet | Pour | Nécessaire ? |
|---|---|---|---|
| web (dev) | `dedoc/scramble` | Générer `openapi.yaml` depuis le code | oui, pour le critère OpenAPI |
| mobile | paquets de `create-expo-app` | Squelette Expo | oui |
| mobile | `expo-secure-store`, `@tanstack/react-query` | Option B (b) | seulement si (b) |

Défaut sans réponse : aucune dépendance hors `create-expo-app` ; le critère
OpenAPI est alors reporté et noté en suspens.

## Avenant — 2026-11-15 : `name` conservé comme pseudonyme

Demande de l'humain en cours de chantier, consignée dans
`.ia/context/decisions/2026-11-15-name-pseudonyme-unique.md` ; remplace
l'arbitrage A.

- `name` est conservé, **obligatoire et unique** : c'est le pseudonyme.
  `first_name` et `last_name` restent l'identité civile.
- La colonne, déjà supprimée par la migration de l'étape 1 (commitée), est remise
  par une nouvelle migration ; les comptes existants reçoivent « prénom nom »,
  suffixé en cas de doublon.
- `auth:create-user` reçoit `--name`.
- `UserResource` (étape 2) expose `name`.

Critères ajoutés à l'étape 1 :
- Le pseudonyme est saisi à la création et modifiable ; il est obligatoire ; un
  pseudonyme déjà pris est refusé (formulaire, commande, contrainte en base).
- La liste du back-office affiche le pseudonyme, triable et cherchable.
- Les comptes existants ont un pseudonyme unique après migration.

---

# Clôture — 2026-11-15

## Ce que j'ai décidé seul

- **`App\Support\Locales`** : domicile unique des langues (lit `config('app.supported_locales')`),
  utilisé par le formulaire, la table, la commande et le middleware de langue. Les noms de langue
  sont écrits dans leur propre langue (« Français », « English »), via intl, sans fichier de traduction.
- **Suppression groupée** : `authorizeIndividualRecords('delete')` dans la base commune
  `AbstractResource` — l'audit a montré qu'elle permettait de supprimer son propre compte, contre une
  décision du registre. Prouvé par un test qui échoue sans le correctif.
- **`HasLocalePreference`** sur `User` : les notifications Laravel futures partiront dans la langue du compte.
- **Langue des réponses de l'API** (`App\Http\Middleware\SetLocale`, groupe `api`) : langue du compte
  connecté, sinon `Accept-Language`, sinon `fr`.
- **Forme de la réponse de connexion** : `{ "data": <UserResource>, "token": "…" }`.
- **Déconnexion** : ne révoque que le jeton de la requête ; les autres appareils restent connectés.
- **Contrat OpenAPI en JSON** (`openapi.json`) et non `openapi.yaml` comme écrit au contrat : Scramble
  n'exporte que du JSON (sous-ensemble valide de YAML). Régénération : `ddev composer openapi`
  (Scramble lit le schéma de la base, donc sous DDEV).
- **Route par défaut `GET /api/user` retirée** : elle renvoyait le modèle brut, sans garde ni test.
- **Mobile** : textes fr/en dans l'app (langue du compte, sinon de l'appareil) ; jeton dans
  `localStorage` sur le web, réservé au développement ; API locale en HTTP (certificat DDEV non reconnu
  par le simulateur) ; nom de l'app `yamo`, schéma `yamo` ; exemples du modèle retirés ; correctif
  du hook `use-color-scheme.web.ts` du modèle (erreur de lint) ; `src/expo-types.d.ts` pour que
  `tsc` passe sans `expo start` préalable.

## Ce qui a été fait

- **Étape 1** (web, `ee42adf`) : `first_name`, `last_name`, `locale` ; reprise du nom ; commande
  `auth:create-user` ; ressource Filament ; suppression groupée sûre. qa-check : GO.
- **Avenant** (web, `b56e680`) : `name` conservé, obligatoire et unique (pseudonyme), remis par une
  nouvelle migration ; reprise « prénom nom » suffixée ; test de la reprise. qa-check : GO.
- **Étape 2** (web, `802ec62`) : `POST /api/v1/auth/login`, `POST /logout`, `GET /me` ; FormRequest,
  service, JsonResource, policy `me`, limite 5/min ; balayage des gardes de routes ; `openapi.json`.
  qa-check : GO. Parcours réel vérifié en HTTP.
- **Étape 3** (mobile, `9750609`) : Expo SDK 58, connexion, accueil, déconnexion. qa-check : GO.
  Parcours réel vérifié dans le navigateur (Expo web).
- Tests web : 49 au vert.

## Ce que je n'ai pas vérifié

- **L'app dans le simulateur iOS** : aucun runtime iOS n'est installé sur la machine. Le parcours a
  été vérifié en Expo web (même code, sauf le stockage du jeton : `localStorage` au lieu de SecureStore).
- Android (émulateur ou appareil) et SecureStore sur appareil réel.
- Le comportement de la limite de connexion derrière un proxy (clé e-mail + IP).
- L'exécution interactive (invites) de `auth:create-user` ; `down()` des migrations sur des données réelles.

## Ce qui reste en suspens

- Installer un runtime iOS (Xcode → Settings → Components, ou `xcodebuild -downloadPlatform iOS`)
  puis constater l'app dans le simulateur.
- Le 401 « Unauthenticated. » de l'API reste en anglais en `fr` (traduction Laravel manquante).
- Identifiants d'application iOS/Android (`bundleIdentifier`, `package`) et projet EAS : à choisir
  avant le premier build.
- Chantiers suivants annoncés : inscription, mot de passe oublié, modification du profil depuis l'app,
  notifications push.
