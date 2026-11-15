# Contrat — Architecture par feature du mobile et formulaires pilotés par l'API

- **Chantier** : `2026-11-15-mobile-features-formulaires`
- **Dépôts** : `yamo/mobile` (principal) et `yamo/web` (API des formulaires)
- **Demande** (chat, 2026-11-15) :
  > Pour la partie mobile : 1. `features/auth/services` générer des classes qui
  > regroupent le même contexte et chaque appel API doit correspondre à une méthode
  > dans la classe ; 2. `features/auth/context` ; 3. `features/auth/providers` ;
  > 4. `features/auth/hooks` ; 5. `features/auth/screens` ; 6. `features/auth/types` ;
  > 7. `features/auth/components` ; 8. `features/auth/utils`. Il faut prévoir un
  > feature form pour gérer les formulaires. Donc les données des formulaires
  > (label, type, name, options, …) sont envoyées par l'API et le formulaire ne fait
  > que l'affichage. Dans `features/form/components` prévoir les différents types de
  > champs (text, select, radio, checkbox, …).
- **Complément** (chat, 2026-11-15, avant validation) :
  > le mobile doit se mettre dans la langue de l'utilisateur connecté et prévoir un
  > changement de langue dans le profil. Si l'utilisateur n'est pas connecté,
  > détecter la meilleure langue selon son appareil et les langues disponibles.
- **Statut** : en attente de validation

## Ce que j'ai compris

### Mobile — une feature = un dossier

Chaque feature vit dans `src/features/<feature>/` avec, selon ses besoins, ces
dossiers (un dossier vide n'est pas créé) :

| Dossier | Contenu | Pour `auth` |
|---|---|---|
| `services/` | Classes qui regroupent les appels API d'un même contexte ; **un appel API = une méthode** | `AuthService` : `login()`, `logout()`, `me()` |
| `types/` | Types TypeScript de la feature (réponses de l'API, état) | `User`, `LoginCredentials`, `AuthState` |
| `context/` | Le `createContext` et sa forme | `AuthContext` |
| `providers/` | Le composant qui fournit le contexte | `AuthProvider` |
| `hooks/` | Les hooks qui exposent la feature | `useAuth()` |
| `screens/` | Les écrans complets ; les fichiers de `src/app/` (routes Expo Router) ne font que les importer | `SignInScreen`, `HomeScreen` |
| `components/` | Composants propres à la feature | bouton de déconnexion, carte de profil |
| `utils/` | Fonctions sans état | stockage du jeton |

Le code partagé par plusieurs features (client HTTP, configuration, thème,
traductions de l'interface) vit hors de `features/` — **voir arbitrage A**.

### Mobile — feature `form` : afficher, pas décider

- L'API envoie la **définition** d'un formulaire : ses champs (`name`, `type`,
  `label`, `placeholder`, `required`, `options`, valeur par défaut, aide) et son
  bouton d'envoi (libellé). Les libellés arrivent **déjà traduits** (langue du
  compte, sinon de l'appareil, comme le reste de l'API).
- `features/form` affiche la définition : un composant par type de champ dans
  `features/form/components/fields/` (liste à l'arbitrage B), un composant
  `DynamicForm` qui choisit le composant selon `type`, garde les valeurs saisies,
  et affiche sous chaque champ les **erreurs de validation renvoyées par l'API**
  (422).
- Le formulaire **ne valide rien lui-même** au-delà de l'obligatoire signalé à
  l'écran : la règle vit côté serveur (une seule source).
- `features/form/services` : `FormService.get(nom)` (une méthode par appel API).
- Premier usage : l'écran de connexion affiche le formulaire `auth.login` reçu
  de l'API, au lieu de ses champs écrits en dur.

### Web — l'API des formulaires

- Un formulaire se **déclare côté Laravel**, dans le module qui le possède
  (ex. `Modules/Auth/app/Forms/LoginForm.php`), avec ses champs **et ses règles
  de validation** : la FormRequest de l'action lit les règles du même formulaire
  (`LoginRequest::rules()` → `LoginForm`). Ainsi l'écran mobile et la validation
  serveur ne peuvent pas diverger.
- Les briques communes (types de champs, construction, sérialisation, registre
  des formulaires) vivent dans un module **`Form`** (`Modules/Form`) — voir
  arbitrage C.
- Route : `GET /api/v1/forms/{form}` (ex. `/api/v1/forms/auth.login`) → la
  définition en JSON, par une `JsonResource`. Formulaire inconnu → 404.
- **Accès** : chaque formulaire dit s'il est public (connexion) ou réservé à un
  compte connecté ; la garde est posée sur la route et vérifiée par le test de
  balayage des routes.
- `openapi.json` régénéré.

### Langue de l'app

- **Connecté** : toute l'app (textes de l'interface, réponses et formulaires de
  l'API) est dans la langue du compte (`locale`). Déjà le cas pour l'interface ;
  conservé après la restructuration.
- **Non connecté** : l'app choisit la **meilleure langue disponible** en parcourant
  les langues préférées de l'appareil **dans leur ordre** (ex. `de-CH`, `en-GB`,
  `fr` → `en`), et non plus la seule première ; aucune ne correspond → la langue
  par défaut de l'API (`fr`).
- **Langues disponibles** : une seule source, `config('app.supported_locales')`
  côté Laravel, exposée par `GET /api/v1/locales` (code + nom dans sa propre
  langue, ex. `fr` « Français »), publique. L'app ne retient que celles dont elle a
  les textes d'interface ; si l'API est injoignable au démarrage, elle se rabat
  sur ses propres langues.
- **Changement de langue dans le profil** : un écran Profil (accessible depuis
  l'accueil) affiche le formulaire **`auth.profile.locale`** reçu de l'API (premier
  formulaire de modification : un champ `locale` de type `select` ou `radio`,
  options = langues disponibles). L'envoi appelle
  `PATCH /api/v1/auth/me` (`AuthService.updateMe()` — ou `ProfileService`, voir G),
  qui modifie la langue du compte ; **l'app bascule aussitôt** dans la nouvelle
  langue, et la suivante connexion la retrouve.
- Côté web, `PATCH /api/v1/auth/me` : FormRequest qui lit les règles du formulaire
  `auth.profile.locale`, policy `update` (branche hors Filament : son propre
  compte), réponse `UserResource`. Les champs non prévus sont ignorés (pas de
  modification d'e-mail, de rôle… par cette route).

## Découpage

1. **Web** — module `Form`, formulaires `auth.login` et `auth.profile.locale`,
   routes `GET /api/v1/forms/{form}`, `GET /api/v1/locales`,
   `PATCH /api/v1/auth/me`, tests → qa-check → commit.
2. **Mobile** — restructuration de `auth` en `features/auth/*` sans changer le
   comportement, détection de la meilleure langue de l'appareil → qa-check → commit.
3. **Mobile** — feature `form` (types de champs, `DynamicForm`, `FormService`),
   écran de connexion branché sur `auth.login`, écran Profil avec changement de
   langue → qa-check → commit.

## Critères de succès

### Étape 1 (web)
- `GET /api/v1/forms/auth.login` renvoie la définition du formulaire de connexion
  (champs `email`, `password`, `device_name` exclu de l'affichage — voir D),
  libellés traduits selon `Accept-Language` (fr, en).
- Un formulaire inconnu renvoie 404 ; un formulaire réservé demandé sans jeton
  renvoie 401.
- Les règles de validation de `POST /api/v1/auth/login` viennent de la même
  déclaration que l'affichage ; les tests de l'API de connexion passent inchangés.
- `GET /api/v1/locales` renvoie les langues de `config('app.supported_locales')`
  avec leur nom ; publique.
- `PATCH /api/v1/auth/me` avec un jeton modifie la langue du compte (valeur
  disponible) et renvoie l'utilisateur ; une langue indisponible → 422 ; sans
  jeton → 401 ; un champ non prévu (`email`, `name`, rôle) n'est pas modifié.
- `GET /api/v1/forms/auth.profile.locale` sans jeton → 401 ; avec jeton → la
  définition, options = langues disponibles, valeur actuelle du compte.
- Le test de balayage des routes passe ; `openapi.json` décrit les nouvelles routes.

### Étape 2 (mobile)
- Le code `auth` est rangé dans `src/features/auth/` selon le tableau ci-dessus ;
  `AuthService` expose une méthode par appel API (`login`, `logout`, `me`).
- `src/app/` ne contient plus que des routes qui importent les écrans.
- Non connecté, la langue retenue est la première des langues préférées de
  l'appareil qui soit disponible (cas testés : `de-CH, en-GB, fr` → `en` ;
  `fr-CH` → `fr` ; `de` seul → `fr`) ; connecté, la langue du compte.
- Le parcours connexion → accueil → déconnexion fonctionne comme avant
  (constaté dans le navigateur, Expo web).
- `npm run typecheck` et `npm run lint` passent.

### Étape 3 (mobile)
- Chaque type de champ retenu (arbitrage B) a son composant dans
  `features/form/components/fields/` et s'affiche correctement à partir d'une
  définition JSON.
- L'écran de connexion n'écrit plus ses champs : il affiche `auth.login` reçu de
  l'API ; changer un libellé côté Laravel change l'écran sans toucher au mobile.
- Une erreur 422 s'affiche sous le champ concerné.
- Depuis l'écran Profil, choisir une autre langue l'enregistre sur le compte et
  bascule l'app aussitôt (textes de l'interface et formulaires de l'API) ; après
  déconnexion puis reconnexion, la langue choisie est retrouvée (constaté dans le
  navigateur).
- Tests automatisés du rendu des champs et de l'affichage des erreurs — **voir
  dépendances (arbitrage E)**.
- `npm run typecheck`, `npm run lint` passent ; parcours de connexion constaté
  dans le navigateur.

## Arbitrages

### A — Où vit le code partagé du mobile ?
1. **Constat** : client HTTP (`src/lib/api.ts`), configuration (`src/config.ts`),
   thème (`src/components`, `src/constants`, `src/hooks`) et traductions de
   l'interface (`src/i18n.ts`) servent plusieurs features.
2. **Options** : (a) un dossier `src/shared/` (`shared/api/ApiClient.ts`,
   `shared/config.ts`, `shared/i18n.ts`, `shared/theme/…`) ; (b) laisser à la racine
   de `src/` comme aujourd'hui.
3. **Recommandation** : (a) — la frontière « feature / partagé » se lit dans
   l'arborescence ; `ApiClient` (classe : URL, en-têtes, jeton, erreurs) est la base
   dont héritent ou que reçoivent les services.
4. **Défaut sans réponse** : (a).
5. **Bloqué en attendant** : étape 2.

### B — Quels types de champs ?
1. **Constat** : la demande cite text, select, radio, checkbox « … ».
2. **Options** : (a) `text`, `email`, `password`, `number`, `textarea`, `select`,
   `radio`, `checkbox` (case unique), `checkbox_group` (choix multiples),
   `switch` ; (b) (a) + `date` / `datetime`, qui demandent une dépendance
   (`@react-native-community/datetimepicker`).
3. **Recommandation** : (a) maintenant, `date` au premier formulaire qui en a besoin.
4. **Défaut sans réponse** : (a).
5. **Bloqué en attendant** : étapes 1 et 3.

### C — Où vivent les formulaires côté Laravel ?
1. **Constat** : le projet est découpé en modules nwidart ; un formulaire
   appartient à un module (connexion → Auth).
2. **Options** : (a) module `Form` pour les briques communes + chaque formulaire
   dans son module, inscrit au registre ; (b) tout dans `app/Forms`.
3. **Recommandation** : (a) — cohérent avec le découpage existant, et les
   formulaires suivent leur module.
4. **Défaut sans réponse** : (a).
5. **Bloqué en attendant** : étape 1.

### D — Champs que l'utilisateur ne voit pas
1. **Constat** : la connexion exige `device_name`, que l'app renseigne seule (nom
   du téléphone).
2. **Options** : (a) type `hidden` dans la définition, rempli par l'app ; (b) hors
   définition : l'écran l'ajoute aux valeurs avant l'envoi.
3. **Recommandation** : (b) — la définition décrit ce que l'utilisateur saisit ;
   ce que l'app sait d'elle-même reste dans l'app. Les règles serveur restent
   complètes (la FormRequest ajoute `device_name` aux règles du formulaire).
4. **Défaut sans réponse** : (b).
5. **Bloqué en attendant** : étapes 1 et 3.

### F — Lire les langues préférées de l'appareil
1. **Constat** : l'app lit aujourd'hui `Intl…resolvedOptions().locale`, une seule
   langue ; la liste ordonnée des langues préférées n'est fournie que par
   `expo-localization` (`getLocales()`), qui suit aussi un changement de langue du
   système.
2. **Options** : (a) ajouter `expo-localization` ; (b) garder `Intl` (une seule
   langue : `de-CH, en-GB` donnerait `fr` au lieu de `en`).
3. **Recommandation** : (a) — c'est ce qui rend « la meilleure langue » possible.
4. **Défaut sans réponse** : (b), critère de détection limité à la première langue
   et noté en suspens.
5. **Bloqué en attendant** : la détection (étape 2), pas la restructuration.

### G — Où ranger la modification du compte côté mobile ?
1. **Constat** : la demande veut des services qui « regroupent le même contexte ».
2. **Options** : (a) tout dans `AuthService` (`login`, `logout`, `me`,
   `updateMe`) ; (b) `AuthService` (session : `login`, `logout`, `me`) et une feature
   `profile` avec `ProfileService.update()` et l'écran Profil.
3. **Recommandation** : (b) — la session et le profil sont deux contextes ; le
   profil grandira (pseudonyme, nom, mot de passe). La langue courante, elle,
   reste exposée par `useAuth()` (elle dépend de l'utilisateur connecté).
4. **Défaut sans réponse** : (b).
5. **Bloqué en attendant** : étape 3.

### E — Dépendances à autoriser
| Où | Paquet | Pour | Nécessaire ? |
|---|---|---|---|
| mobile (dev) | `jest-expo`, `jest`, `@testing-library/react-native`, `@types/jest` | Tests du rendu des champs, des erreurs et de la détection de langue | oui, pour les critères de tests |
| mobile | `expo-localization` | Langues préférées de l'appareil, dans l'ordre (arbitrage F) | oui, si F (a) |
| mobile | (aucune autre) | sélecteur, radio, case, interrupteur faits avec les composants React Native | — |
| web | (aucune) | | — |

Défaut sans réponse : aucune dépendance ; le critère de tests de l'étape 3 est
reporté et noté en suspens.

## Avenant — 2026-11-15 : contrôleurs et services Login, Logout, Profile

Demande de l'humain en cours de chantier, consignée dans
`.ia/context/decisions/2026-11-15-services-login-logout-profile.md` (remplace
l'arbitrage G) : côté Laravel, `LoginController`/`LoginService`,
`LogoutController`/`LogoutService`, `ProfileController`/`ProfileService` à la place
d'`AuthController`/`AuthService`, URL inchangées ; côté mobile, `LoginService`,
`LogoutService`, `ProfileService` (`get`, `update`) dans `features/auth/services`,
écran Profil dans `features/auth/screens` (pas de feature `profile`).

---

# Clôture — 2026-11-15

## Ce que j'ai décidé seul

- **Langue par défaut stable** (`config('app.default_locale')`, `Locales::default()`) :
  `config('app.locale')` est réécrite par `App::setLocale()` à chaque requête ; la langue
  par défaut renvoyée par `GET /api/v1/locales` était fausse (bug trouvé par un test).
- **Garde Sanctum sur tout le groupe `api`** (`UseSanctumGuard`) : une route ouverte aux
  invités reconnaît quand même le compte du jeton ; c'est le formulaire qui décide de son
  accès (capacité `viewForm` : 401 invité, 403 refusé).
- **Schéma Bearer** déclaré dans `openapi.json` (relevé par l'audit).
- **Placement mobile des services Login/Logout/Profile** dans `features/auth/services`
  (la demande ne disait pas où).
- **`useI18n()` partagé** (`src/shared/i18n/I18nContext`) : la feature `form` lit la langue
  sans dépendre de la feature `auth`, qui la décide.
- **Sélecteur sans dépendance** : liste d'options dans une fenêtre modale.
- **`FormLoader`** : charge la définition et remonte le formulaire quand la langue change.
- **Correctifs d'outillage** : types Jest déclarés dans `tsconfig.json` (TypeScript 6),
  CSS remplacé par un module vide dans Jest, `package.json`/`app.json` remis à 2 espaces.
- **Entrée envoie le formulaire** : régression de l'ancien écran de connexion, rétablie.

## Ce qui a été fait

- **Web** (`e63f966`, `6471f96`) : module `Form` (11 types de champs, règles dérivées,
  registre), formulaires `auth.login` et `auth.profile.locale` dont les FormRequest lisent les
  règles, `GET /api/v1/forms/{form}`, `GET /api/v1/locales`, `PATCH /api/v1/auth/me` ;
  contrôleurs et services Login, Logout, Profile. 65 tests.
- **Mobile** (`69442d8`, `c9e4c30`) : `features/auth` (services Login/Logout/Profile, types,
  contexte, provider, hooks, écrans Connexion/Accueil/Profil, composants, utilitaires),
  `src/shared` (ApiClient, config, i18n, thème, LocaleService), meilleure langue de l'appareil
  (`expo-localization`), `features/form` (DynamicForm, FormLoader, `switch` des types, un
  composant par type), écran Profil avec changement de langue. 12 tests.
- 5 verdicts qa-check, tous GO, dans `.ia/runs/2026-11-15-mobile-features-formulaires/`.

## Ce que je n'ai pas vérifié

- **Rendu natif iOS / Android** : toujours aucun runtime iOS sur la machine ; parcours
  vérifiés en Expo web (le jeton y est en `localStorage`, pas dans SecureStore).
- `useLocales()` sur un appareil réel (changement de langue du système à chaud).
- Pas de test automatisé de `FormLoader`, `ProfileScreen`, des services mobiles ni du repli
  « API injoignable ».
- Le comportement de l'API derrière un proxy, et la page de debug en production (APP_DEBUG).

## Ce qui reste en suspens

- **Jetons orphelins** : supprimer un compte (back-office, commande) laisse ses jetons Sanctum
  en base (Sanctum ne les supprime pas). Sans effet sur la sécurité (le jeton ne
  s'authentifie plus), mais à révoquer à la suppression.
- Le chargement de l'app attend la liste des langues (écran de démarrage plus long si l'API
  est lente).
- Type `date` / `datetime` : au premier formulaire qui en a besoin (dépendance à valider).
- Le 401 « Unauthenticated. » reste en anglais en `fr` (chantier précédent).
- Simulateur iOS à installer pour constater l'app en natif.
