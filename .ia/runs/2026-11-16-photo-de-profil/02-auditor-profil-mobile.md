# Tâche 02 — Audit mobile : profil utilisateur et client API

STATUT : TERMINÉ (audit en lecture seule ; aucun fichier du dépôt mobile modifié, rien commité)

Dépôt : `/Users/bertrandzang/home/private/yamo/mobile` (HEAD `7aee40f`). Chemins ci-dessous relatifs à ce dépôt.
Profondeur : aucun skill d'audit dédié Expo, donc enveloppe seule. La spec est écrite ici, pas dans `.ia/memory/` (le dépôt mobile n'a pas de `.ia/`, comme le demandait le prompt).

## 1. Client API — `src/shared/api/ApiClient.ts`, `errors.ts`, `config.ts`

- **URL de base** : `API_URL` = `process.env.EXPO_PUBLIC_API_URL` sans la barre finale (`config.ts:5,12`). Si la variable manque, une erreur est levée **à l'import du module** (`config.ts:7-9`). Exemple de valeur : `http://yamo.ddev.site/api` (`config.ts:11`, README.md:21). Les chemins des services commencent donc par `/v1/...` (`ProfileService.ts:11`).
- **Méthodes** : `HttpMethod` = GET|POST|PATCH|PUT|DELETE (`ApiClient.ts:5`). Raccourcis existants : `get` (l.31), `post` (l.35), `patch` (l.39). **Pas de raccourci `delete`** : il faut passer par `request(path, { method: 'DELETE' })`, qui est public (l.43).
- **En-têtes** (`ApiClient.ts:49-54`) :
  - `Accept: application/json` toujours (l.50) ;
  - `Accept-Language` = la langue si elle est définie (l.51) ;
  - `Content-Type: application/json` **dès que `body !== undefined`** (l.52) ;
  - `Authorization: Bearer <jeton>` si un jeton est présent (l.53).
- **Corps** : `body === undefined ? undefined : JSON.stringify(body)` (l.55).
- **Jeton et langue** : `setToken` (l.23) et `setLocale` (l.27), appelés par `AuthProvider` (`AuthProvider.tsx:33`, `:70`). `apiClient` est un singleton partagé (`ApiClient.ts:76`).
- **Réponses** :
  - une erreur levée par `fetch` devient `NetworkError` (l.57-59) ;
  - un 204 renvoie `undefined` (l.61-63) ;
  - sinon le JSON est lu, et un JSON illisible donne `{}` (l.65) ;
  - si la réponse n'est pas ok, `ApiError(status, payload.message ?? statusText, payload.errors)` (l.67-69).
- **`ApiError`** (`errors.ts:2-16`) : `status` ; `errors: Record<string,string[]>` qui vaut `{}` par défaut (l.6) ; `rejectsToken` vrai pour 401/403 (l.13-15). `NetworkError` porte le message 'Network request failed' et la cause (l.19-23).

### Pièges pour envoyer du FormData (multipart)
1. Passer un `FormData` comme `body` à `post` le ferait **sérialiser par `JSON.stringify`** (`ApiClient.ts:55`), ce qui donne `{}`, et forcerait `Content-Type: application/json` (l.52). Il faut une branche FormData qui transmet le corps tel quel et **n'envoie aucun `Content-Type`**, pour que `fetch` pose `multipart/form-data; boundary=...`. Un `Content-Type` forcé à la main n'aurait pas de boundary.
2. `Accept: application/json` (l.50) doit rester : c'est lui qui fait renvoyer un 422 en JSON (`errors.avatar`) plutôt qu'une redirection.
3. `RequestOptions.body` est typé `unknown` (l.9). Rien dans le type n'empêche donc l'erreur du point 1.

## 2. Service de profil — `src/features/auth/services/ProfileService.ts`

- Le client est injecté par le constructeur, `apiClient` par défaut (l.7). C'est ce qui permet de le tester avec un faux client.
- `get()` appelle `GET /v1/auth/me` et renvoie `.data` (l.10-12).
- `update(values)` appelle `PATCH /v1/auth/me` et renvoie `.data` (l.18-20).
- Un singleton `profileService` est exporté (l.23).
- Convention « une méthode par appel API » : `ProfileService.ts:5`, README.md:64.
- Rien n'existe encore pour la photo (`uploadAvatar` et `removeAvatar` sont absents).

## 3. Types — `src/features/auth/types/index.ts`

- `User` = `{ id, name, first_name, last_name, email, locale }` (l.4-11). **Pas de champ `avatar_url`.** Le commentaire renvoie à `UserResource` dans `openapi.json` (l.3).
- `UserResponse = { data: User }` (l.23). `LoginResponse = { data: User; token }` (l.20).
- `AuthContextValue.setUser: (user: User) => void` (l.35). Son commentaire : « après une modification de son compte » (l.34).

## 4. Session — `AuthProvider.tsx`, `useAuth.ts`, `AuthContext.ts`

- L'utilisateur vient de la requête React Query `['auth','me', token]`, qui appelle `profileService.get()` (`AuthProvider.tsx:20,52-57`). Il est relu depuis l'API à chaque démarrage, une fois le jeton lu dans le stockage (l.37-39). Une photo enregistrée côté serveur réapparaît donc après redémarrage, par `GET /me`.
- `setUser(updated)` = `queryClient.setQueryData([...ME_KEY, token], updated)` (l.92-95). C'est le chemin prévu pour réinjecter l'utilisateur que renvoient POST/DELETE avatar.
- `user` vaut `meQuery.data` si un jeton existe, sinon `null` (l.66). `locale` = `user?.locale ?? deviceLocale` (l.67).
- `useAuth()` lève une erreur hors `<AuthProvider>` (`useAuth.ts:9-11`). Un composant qui l'utilise doit donc être monté sous le fournisseur en test, ou bien recevoir `useAuth` mocké.
- `I18nContext` est fourni à côté (`AuthProvider.tsx:110-115`) et a une valeur par défaut en français (`I18nContext.tsx:15-18`). Un composant qui utilise `useI18n()` se teste donc **sans** fournisseur.
- Le QueryClient est créé dans `src/app/_layout.tsx:14`. Les routes `index` et `profile` sont protégées par `user !== null` (`_layout.tsx:43-46`).

## 5. Écrans et composants

- **Home** (`HomeScreen.tsx`) : `useAuth().user`, `null` sans utilisateur (l.14-18). Contenu centré : `<UserSummary user={user} />` (l.23), puis les actions `ProfileLink` et `SignOutButton` (l.24-27). Styles : `safeArea` centrée avec `gap: Spacing.two` (l.35).
- **UserSummary** (`UserSummary.tsx`) : prend `user` en prop et lit `t` par `useAuth()` (l.7-8). Il affiche dans l'ordre :
  - `home.greeting` en `subtitle` (l.12) ;
  - `home.signedInAs` avec `fullName` = `first_name last_name` après trim (l.13-15) ;
  - `home.email` (l.16).

  Le tout est enveloppé dans un fragment, sans conteneur (l.11-17).
- **Profile** (`ProfileScreen.tsx`) :
  - lien retour `testID="profile-back"` (l.34-36), puis titre `profile.title` (l.37) ;
  - `<FormLoader name="auth.profile.locale" onSubmit={save} />` (l.38) ;
  - `save` appelle `profileService.update` puis `setUser`, et met `saved` à vrai (l.24-28) ;
  - message `testID="profile-saved"` (l.39-43) ;
  - n'affiche pas d'informations sur l'utilisateur et ne lit pas `user` (l.21 : seulement `setUser`, `t`) ;
  - mise en page : `content` avec `width: '100%'`, `maxWidth: MaxContentWidth / 2` et `gap: Spacing.three` (l.53).
- **Affichage des erreurs, modèle existant (module Form)** :
  - `DynamicForm` attrape `ApiError` et range `errors` par champ (`DynamicForm.tsx:47-53`) ;
  - `NetworkError` donne `t('error.network')`, toute autre erreur `t('error.unknown')` (l.54-58) ;
  - le texte d'erreur est rendu avec `ERROR_COLOR = '#d93025'` (`FieldShell.tsx:9`), `type="small"`, `accessibilityRole="alert"` et `testID={name}-error` (`FieldShell.tsx:27-31`) ;
  - premier message d'un champ : `fieldError` (`values.ts:21-23`).

  Le contrat place le composant photo hors du module Form : il devra refaire ce traitement lui-même. `ERROR_COLOR` est exporté depuis `features/form`.

## 6. i18n — `src/shared/i18n/messages.ts`

- Langues `fr`, `en` (l.7) ; `DEFAULT_LOCALE = 'fr'` (l.11).
- Clés à plat sous `fr` (l.14-31) et `en` (l.32-49). `satisfies Record<Locale, Record<string,string>>` (l.50) ; `MessageKey` est déduit de `fr` (l.52).
- Une clé ajoutée en `fr` seulement casse donc `translate` en `en` à l'exécution (`messages[locale][key]`, `locale.ts:31`) **sans erreur de type certaine**. Voir Incertitudes.
- Paramètres `{nom}` (`locale.ts:31`).
- Clés de profil existantes : `profile.title`, `profile.saved`, `profile.back`. Clés d'erreur : `error.network`, `error.unknown`. Aucune clé photo.

## 7. Thème

- `Colors.light/dark` : `text`, `background`, `backgroundElement`, `backgroundSelected`, `textSecondary` (`theme.ts:10-25`).
- `Spacing` : half 2, one 4, two 8, three 16, four 24, five 32, six 64 (`theme.ts:54-62`). `MaxContentWidth = 800` (l.65).
- `useTheme()` renvoie `Colors[light|dark]` (`use-theme.ts:9-14`). Pour un fond d'initiales : `backgroundElement` / `backgroundSelected`.
- `ThemedView` accepte `lightColor` et `darkColor` mais les **ignore**, seul `type` compte (`themed-view.tsx:12-15`).
- `ThemedText` : `type` et `themeColor` (`themed-text.tsx:6-15`).
- Couleur d'accent codée en dur : `#208AEF` (`DynamicForm.tsx:104`) ; lien `#3c87f7` (`themed-text.tsx:50`).

## 8. Image

- `expo-image` ~58.0.18 est déclaré (`package.json:13`) et installé (node_modules, version 58.0.18), mais **aucun import dans `src/`** (grep vide).
- `expo-image-picker` est **absent** de `package.json` et de `node_modules`.
- Plugins dans `app.json:26-38` : `expo-router`, `expo-splash-screen`, `expo-secure-store`, `expo-localization`.
- Ajouter une dépendance se fait par `npx expo install` (README.md:91, AGENTS.md:16). Une bibliothèque native impose une development build, pas Expo Go (AGENTS.md:40).
- AGENTS.md:5-9 impose de lire la doc Expo **v58** avant d'écrire du code Expo (API de `expo-image-picker` et `expo-image` à vérifier là).

## 9. Tests — jest-expo et RNTL 14

- Configuration jest : `preset: jest-expo`, et `.css` résolu par `jest/style-mock.js` (`package.json:52-57`). **Pas de setupFiles.** Versions installées : `@testing-library/react-native` 14.1.0, `jest-expo` 58.0.14.
- Deux suites :
  - `src/features/form/__tests__/DynamicForm.test.tsx` ;
  - `src/shared/i18n/__tests__/locale.test.ts` (fonctions pures).
- Style RNTL 14 asynchrone : `await render(...)` (`DynamicForm.test.tsx:38`), `await fireEvent.press/changeText` (l.64-71), `waitFor` (l.73), `findByTestId` (l.97), matchers `toBeOnTheScreen`, `toHaveTextContent`, `toHaveProp`, `toHaveAccessibilityValue` (l.41, 97, 123, 49).
- **Comment l'API est simulée** : nulle part par `jest.mock` ni par `fetch`. Les tests agissent sur la frontière du composant : `onSubmit = jest.fn().mockRejectedValue(new ApiError(422, 'Invalid', {nickname: [...]}))` (l.90-92). Aucun test ne monte `AuthProvider`, `ApiClient` ou un service.
- Aucun test n'existe pour `ApiClient`, `ProfileService`, les écrans ou `UserSummary`.
- Point d'injection disponible pour les tests de service : `new ProfileService(fauxClient)` (`ProfileService.ts:7`).

## Ce que l'audit ne couvre pas / incertitudes

- **Env en test** : `config.ts:7-9` lève une erreur à l'import si `EXPO_PUBLIC_API_URL` est absente. Aucun test actuel n'importe `ApiClient` (le test Form n'importe que `errors.ts`). Je n'ai pas vérifié que jest-expo injecte le `.env` : un test qui importe `ApiClient` ou `ProfileService` pourrait échouer à l'import. À vérifier en lançant un test ; sinon, poser la variable dans le test ou dans une configuration jest.
- **i18n** : je n'ai pas vérifié si `satisfies` combiné à `MessageKey` (clés de `fr`) signale une clé manquante en `en` au typage. Ajouter chaque clé dans les deux langues.
- **jest-expo et `expo-image` / `expo-image-picker`** : je n'ai pas vérifié que ces modules natifs sont mockés par le preset. Prévoir `jest.mock('expo-image-picker')` dans les tests du composant.
- **FormData en React Native** : la forme `{ uri, name, type }` ajoutée au FormData et le comportement de `fetch` sur la boundary relèvent de la doc RN et Expo v58, pas du code source. Je ne les ai pas audités.
- **Non lus** :
  - champs `fields/*` du module Form, `FormField.tsx`, `token-storage.ts`, `device-name.ts`, `use-color-scheme*`, `global.css` ;
  - `.env` et `.env.example` : la lecture a été refusée par le classificateur de permissions, je n'ai pas insisté.
- **Côté web** : `UserResource` et `openapi.json` ne sont pas audités ici. Le futur `avatar_url: string | null` vient du contrat (`specs/2026-11-16-photo-de-profil.md`), pas du code mobile.

## Fichiers lus (en entier)
- **Documentation et configuration** : README.md, AGENTS.md, package.json, app.json, tsconfig.json, eslint.config.js, jest/style-mock.js.
- **API et i18n** : src/shared/config.ts, src/shared/api/{ApiClient,errors}.ts, src/shared/i18n/{messages,locale}.ts, src/shared/i18n/I18nContext.tsx, src/shared/services/LocaleService.ts.
- **Thème** : src/shared/theme/theme.ts, src/shared/theme/hooks/use-theme.ts, src/shared/theme/components/{themed-text,themed-view}.tsx.
- **Routes** : src/app/{_layout,index,profile}.tsx.
- **Feature auth** :
  - src/features/auth/{types/index.ts, context/AuthContext.ts, hooks/useAuth.ts, providers/AuthProvider.tsx} ;
  - src/features/auth/services/{Profile,Login,Logout}Service.ts ;
  - src/features/auth/screens/{Home,Profile,SignIn}Screen.tsx ;
  - src/features/auth/components/{UserSummary,ProfileLink,SignOutButton}.tsx.
- **Feature form** :
  - src/features/form/components/{FormLoader,DynamicForm,FieldShell}.tsx ;
  - src/features/form/{hooks/useFormDefinition.ts, services/FormService.ts, types/index.ts, utils/values.ts}.
- **Tests** : src/features/form/__tests__/DynamicForm.test.tsx, src/shared/i18n/__tests__/locale.test.ts.
- **Contrat** : /Users/bertrandzang/home/private/yamo/web/specs/2026-11-16-photo-de-profil.md.
