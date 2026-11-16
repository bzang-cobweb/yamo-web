# 02 — Audit mobile (menu du bas et profil)

STATUT : TERMINÉ — lecture seule. Aucun `spec_*.json` écrit : le périmètre d'écriture se limite à ce rapport. Rien n'est commité.
Racine : `/Users/bertrandzang/home/private/yamo/mobile` (chemins relatifs à cette racine). expo-router 58.0.31, expo-symbols 58.0.5.

## 1. Routes actuelles
- `src/app/` : `_layout.tsx`, `index.tsx` (HomeScreen, index.tsx:2-4), `profile.tsx` (ProfileScreen, profile.tsx:2-4), `sign-in.tsx` (SignInScreen). Aucun groupe, aucun `_layout` imbriqué.
- `_layout.tsx` :
  - `SplashScreen.preventAutoHideAsync()` au chargement du module (l.12) ; `hideAsync()` quand `!isLoading || offline` (l.40-44).
  - Ordre d'imbrication : `QueryClientProvider > AuthProvider > ThemeProvider > RootNavigator` (l.20-26).
  - `ConnectivityGate` entoure la `Stack` (l.47) ; la Stack n'est montée que si `!isLoading` (l.48).
  - `Stack screenOptions={{ headerShown: false }}` (l.49). `Stack.Protected guard={user !== null}` contient `index` et `profile` (l.50-53) ; `guard={user === null}` contient `sign-in` (l.54-56).
- `ConnectivityGate` recouvre l'app sans la démonter (ConnectivityGate.tsx:21-36). Le menu du bas, placé dans la Stack, sera donc recouvert par l'écran hors ligne.
- Routes typées :
  - activées dans `app.json:48` (`typedRoutes: true`) ;
  - le fichier `.expo/types/router.d.ts` liste `/`, `/profile`, `/sign-in`, `/_sitemap` (l.9-11) ;
  - `tsconfig.json:20` inclut `.expo/types/**/*.ts`, mais `.expo/` est dans `.gitignore`, donc le fichier n'est pas versionné ;
  - il est régénéré par le watcher du serveur de dev (`@expo/cli/.../type-generation/routes.js:79-104` → `@expo/router-server/build/typed-routes/index.js:70-110`, qui écrit `router.d.ts`).

## 2. API des onglets (`expo-router/js-tabs`)
- Export : `package.json:134-141` `./js-tabs` → `build/layouts/Tabs.{d.ts,js}`. `./tabs` pointe sur le même fichier (l.182-189). `Tabs` importé depuis `expo-router` est `@deprecated` au profit de `expo-router/js-tabs` (build/exports.d.ts:53-55).
- `Tabs` porte `Screen` et `Protected` (`TabsClient.d.ts:35-42`). Une option de Screen ajoute `href?: Href | null` (`TabsClient.d.ts:8-10`).
- Options (`build/react-navigation/bottom-tabs/types.d.ts`) :
  - `hidden?` (l.81) ; `title?` (l.85) ;
  - `tabBarShowLabel?` : true par défaut (l.101) ;
  - `tabBarIcon?({focused,color,size})` (l.122-126) ;
  - `tabBarAccessibilityLabel?` : « recommended … if you don't have a label » (l.140-144) ;
  - `tabBarButtonTestID?` (l.148) ; `tabBarActiveTintColor` / `tabBarInactiveTintColor` (l.157, 161) ;
  - `headerShown?` : **true par défaut** (l.214-218) ;
  - `popToTopOnBlur?` : false par défaut (l.220-223).
  - `backBehavior` est une option du navigateur (`TabRouter.d.ts:30-42`).
- `Protected` : `{ guard, redirectTo?, children }`. Sans `redirectTo`, la redirection va vers l'ancre du navigateur parent (`views/Protected.d.ts:3-8`).
- Structure possible (convention expo-router) :
  - `app/(tabs)/_layout.tsx` (Tabs), avec `(tabs)/index.tsx` et `(tabs)/profile/_layout.tsx` (Stack) ;
  - le profil contient `profile/index.tsx`, `personal.tsx`, `password.tsx`, `preferences.tsx` ;
  - à la racine : `<Stack.Protected guard={user !== null}><Stack.Screen name="(tabs)" /></Stack.Protected>`.
  - Je n'ai trouvé dans les `.d.ts` que des indices indirects pour cette convention : `RouteNode.route` est « relatif au `_layout` le plus proche » (`Route.d.ts:42-43`) et `groupName` (`Route.d.ts:36-37`). Le code de `getRoutes` n'a pas été lu.

## 3. expo-symbols
- `SymbolView` : `name: SFSymbol | { ios?, android?, web? }`, avec `size`, `tintColor` et `fallback` (`build/SymbolModule.types.d.ts:8-16, 46, 50`). Déjà utilisé dans `TextField.tsx:1, 67-71` (forme `{ ios, android, web }`).
- `build/android/symbols.json` : `"home"` (l.1768) et `"person"` (l.2650) existent. `"house"` et `"account_circle"` aussi.
- SF : `'house'`, `'house.fill'`, `'person'`, `'person.fill'` existent (`node_modules/sf-symbols-typescript/dist/index.d.ts:954, 955, 1177, 1206`).

## 4. Écrans et composants
- **HomeScreen** : rend `null` sans user (l.16-18), puis `UserSummary`, `ProfileLink` et `SignOutButton` (l.23-26).
- **UserSummary** : `Avatar` (taille 72 par défaut) + `home.greeting` + `home.signedInAs` (nom complet) + `home.email` (l.13-18). Il est utilisé seulement par HomeScreen.
- **ProfileLink** : `<Link href="/profile" testID="profile-link">` + `home.profile` (l.14-15). Il est utilisé seulement par HomeScreen.
- **SignOutButton** : `testID="sign-out"`, appelle `signOut()`, texte `home.signOut` (l.16-24). Il est utilisé seulement par HomeScreen.
- **ProfileScreen** :
  - le lien retour fait `router.back()` (`testID="profile-back"`, `profile.back`, l.36-37) ; titre `profile.title` (l.39) ;
  - `<AvatarPicker />` (l.40) ; `<FormLoader name="auth.profile.locale" onSubmit={save}>` (l.41) ;
  - `save` appelle `setUser(await profileService.update(values))`, puis affiche `profile.saved` (`testID="profile-saved"`, l.26-30, 42-46).
- **AvatarPicker** : `service` et `pick` sont injectables (l.14-18, 26). Il appelle `setUser` sur le résultat. Ses erreurs :
  - `ApiError` → `errors.avatar[0]` ou `message` ;
  - `NetworkError` → `offline.message` ;
  - autre → `error.unknown` (l.35-52).
  - Le menu passe par `Alert` (l.62-72). Taille 96 (l.20).
- **Avatar** : affiche une image (`avatar-image`) ou les initiales (`avatar-initials`), avec le label `avatar.label` (l.29-49).

## 5. Services, formulaires, session
- **ProfileService** (une méthode par appel) :
  - `get` → GET `/v1/auth/me` (l.11-13) ;
  - `update` → **PATCH `/v1/auth/me`** (l.19-21 ; son commentaire cite `auth.profile.locale`, l.15-18) ;
  - `uploadAvatar` → `postForm` `/v1/auth/me/avatar` (l.27-32) ;
  - `removeAvatar` → DELETE (l.35-37).
- Appelants :
  - `get` : AuthProvider.tsx:72 ;
  - `update` : **ProfileScreen.tsx:28 seulement** ;
  - `uploadAvatar` / `removeAvatar` : AvatarPicker.tsx:58, 66 (et le type `ProfileService`, l.9, 16).
- **ApiClient** : `get`, `post`, `patch`, `postForm` et `delete` existent (l.32-54). **Il n'y a pas de méthode `put`.** Le type `HttpMethod` inclut pourtant `'PUT'` (l.5), et `request()` est public (l.56). Une erreur HTTP lève `ApiError(status, message, errors)` (l.80-83 du fichier).
- **FormLoader** `{ name, onSubmit: (values) => Promise<void> }` (l.13-17) :
  - affiche un loader, ou une erreur avec retry (`form-retry`) ;
  - rend `<DynamicForm key={`${name}-${locale}`}>` (l.51) : un changement de langue remonte le formulaire avec ses valeurs par défaut.
- **DynamicForm.submit** (l.40-62) :
  - une `ApiError` est répartie sous les champs avec `setErrors(error.errors)` ;
  - si aucune clé ne vise un champ affiché, le premier message (ou `error.message`) s'affiche dans `form-message` ;
  - `NetworkError` → `offline.message` ; autre → `error.unknown` ;
  - **après un succès, rien n'est réinitialisé** (valeurs conservées) ;
  - le titre vient de `definition.title` (`form-title`, l.66-79) ;
  - `fieldError` gère les clés `name.N` (utils/values.ts:21-23).
- **AuthProvider** :
  - `ME_KEY = ['auth','me']` (l.21) ; requête `[...ME_KEY, token]` → `profileService.get()` (l.70-76) ;
  - `setUser(updated)` écrit le cache `[...ME_KEY, token]` (l.135-138) ;
  - `signOut` : `logoutService.logout()` (erreur ignorée), `clearToken`, `removeQueries(ME_KEY)`, `applyToken(null)` (l.125-133) ;
  - `locale = user?.locale ?? deviceLocale` (l.86) ; les messages sont rechargés par `['messages', locale]` (l.94-101).
  - Le type est dans `types/index.ts:30-47` (`setUser` documenté « langue… », l.45-46).

## 6. Textes
- `ApiMessageKey` (messages.ts:34-55) : `home.greeting`, `home.signedInAs`, `home.email`, `home.signOut`, `home.profile`, `profile.title`, `profile.saved`, `profile.back`, `form.*` (6 clés), `avatar.*` (6 clés), `error.unknown`. Les clés locales sont `offline.message` et `offline.retry` (l.20-31).
- Utilisations des clés :
  - `home.profile` : ProfileLink:15 ;
  - `home.signOut` : SignOutButton:24 ;
  - `home.greeting`, `home.signedInAs`, `home.email` : UserSummary:14-18 ;
  - `profile.*` : ProfileScreen:37, 39, 44 ;
  - `form.showPassword` / `form.hidePassword` : TextField:62.
- `translate` affiche la clé brute si l'API ne l'a pas envoyée (locale.ts:30-44).
- `TEST_MESSAGES` (test-messages.ts:9-31) est une copie manuelle « de `lang/fr/mobile.php` » (l.6-7). Les clés `home` / `profile` / `avatar` sont en réalité dans `web/Modules/Auth/lang/fr/mobile.php:7-26`. Aucun test ne vérifie la synchronisation (grep `TEST_MESSAGES` : seulement AvatarPicker.test et DynamicForm.test via `TEST_I18N`).

## 7. Tests existants
- `features/auth/__tests__/AvatarPicker.test.tsx` : contexte `AuthContextValue` complet construit à la main (l.31-42) ; textes FR attendus « Choisir une photo », « Retirer la photo », « Annuler ».
- `features/form/__tests__/DynamicForm.test.tsx` : erreurs 422 (l.102-117), message général sur 429 (l.119-127).
- `shared/components/__tests__/ConnectivityGate.test.tsx`, `OfflineScreen.test.tsx`, `shared/api/__tests__/ApiClient.test.ts`, `form-file.test.ts`, `shared/i18n/__tests__/locale.test.ts`, `shared/network/__tests__/connectivity.test.ts`.
- **Aucun test** pour HomeScreen, ProfileScreen, ProfileLink, SignOutButton, UserSummary, `_layout` ou les routes. `expo-router/testing-library` est exporté (package.json:190) mais n'est pas utilisé.

## 8. Pièges
1. **Routes typées** : `router.d.ts` est périmé tant que `expo start` n'a pas tourné. `tsc --noEmit` peut refuser `href="/profile/personal"` avec l'ancien fichier, ou l'accepter sans contrôle sur un clone neuf (fichier absent, ignoré par git).
2. **Le profil devient un dossier** : supprimer `app/profile.tsx` au profit de `(tabs)/profile/index.tsx` (+ `_layout.tsx`), sinon il y aura un conflit de route `/profile`. `ProfileLink` (`href="/profile"`) disparaît de l'accueil. Les nouveaux liens visent `/profile/personal`, `/profile/password`, `/profile/preferences` : les groupes `(…)` n'apparaissent pas dans l'URL (`SingleRoutePart` exclut `(${string})`, typed-routes/types.d.ts:103).
3. **Stack racine** : `Stack.Screen name="index"` et `name="profile"` (`_layout.tsx:51-52`) doivent être remplacés par `name="(tabs)"`, sinon la garde ne s'applique plus aux bons écrans.
4. **En-tête des onglets** visible par défaut (`headerShown` true, types.d.ts:218) : il faut `headerShown: false` sur Tabs et sur la Stack du profil.
5. **Un fichier de formulaire placé directement dans `(tabs)/`** devient un onglet. Il faut une Stack imbriquée, ou `href: null` / `hidden`.
6. **Retour dans le profil** : le `router.back()` actuel (ProfileScreen:36) n'a plus de sens sur l'écran racine d'un onglet ; le lien Retour va sur les écrans de formulaire. `popToTopOnBlur` est false par défaut : rouvrir l'onglet Profil ramène au formulaire laissé ouvert. Le contrat ne tranche pas ce point.
7. **`auth.profile.locale` disparaît** :
   - ProfileScreen.tsx:41 et ses commentaires (ProfileScreen:17-21) ;
   - le commentaire de FormLoader.tsx:14 et celui de ProfileService.ts:15-18 ;
   - `ProfileService.update` (PATCH `/v1/auth/me`) n'a qu'un seul appelant (ProfileScreen:28).
8. **PUT mot de passe** : `ApiClient` n'a pas de `put()`. Il faut l'ajouter (et le tester), ou passer par `request(path, { method: 'PUT', body })`.
9. **Préférences** : changer la langue change `locale` (AuthProvider:86), ce qui remonte le `DynamicForm` (FormLoader:51) et recharge les messages. Le message de confirmation doit vivre hors du formulaire (comme `saved` aujourd'hui, ProfileScreen:24). Le retour au profil avec confirmation (contrat) n'a pas de mécanisme existant (paramètre de route ou état partagé) : c'est à concevoir.
10. **Mot de passe** : l'erreur « ancien faux » doit être une 422 sur un champ. Un 401/403 ferait oublier le jeton s'il touchait `me` (`rejectsToken`, errors.ts:13-15). La requête mot de passe n'est pas `meQuery`, donc il n'y a pas d'effet direct, mais elle afficherait un message général. `DynamicForm` ne vide pas les champs après succès.
11. **Textes** :
    - chaque nouvelle clé doit aller dans `ApiMessageKey`, dans `TEST_MESSAGES` (copie manuelle) et dans les deux `mobile.php` (fr/en) du module web ;
    - `home.profile` devient inutilisé ; `home.signOut` est déplacé vers le profil (clé « home » sur un écran profil) ;
    - les libellés d'onglets doivent passer par `t()` pour `tabBarAccessibilityLabel` ;
    - les textes ne sont pas disponibles pendant `isLoading`, mais la Stack n'est montée qu'après (`_layout.tsx:48`).
12. **Accueil** : `UserSummary` affiche aujourd'hui aussi le nom complet et l'e-mail (l.15-18). Le contrat ne garde à l'accueil que photo + salutation, et met nom, prénom et e-mail en tête du profil. Le découpage du composant est à décider.

## Fichiers lus (intégralement sauf mention)
src/app/{_layout,index,profile,sign-in}.tsx · src/expo-types.d.ts · features/auth/{screens/HomeScreen,screens/ProfileScreen,screens/SignInScreen,components/*,services/ProfileService,providers/AuthProvider,context/AuthContext,hooks/useAuth,types/index} · features/auth/__tests__/AvatarPicker.test.tsx · features/form/{components/FormLoader,components/DynamicForm,components/fields/TextField,hooks/useFormDefinition,services/FormService,types/index,utils/values} · features/form/__tests__/DynamicForm.test.tsx (l.1-60, 100-130 + liste des `it`) · shared/i18n/{messages,test-messages,locale,I18nContext} · shared/api/{ApiClient,errors} · shared/components/ConnectivityGate.tsx · ConnectivityGate.test.tsx (l.1-25) · package.json, app.json, tsconfig.json, .gitignore, AGENTS.md, .expo/types/router.d.ts · node_modules : expo-router package.json (exports), build/layouts/{Tabs,TabsClient,createBaseTabProps,GuardContext,useClearGuardedRoutes}.d.ts, build/views/Protected.d.ts, build/react-navigation/bottom-tabs/types.d.ts (l.60-230), routers/TabRouter.d.ts (l.30-50), build/Route.d.ts (l.1-60), typed-routes/types.d.ts (grep), exports.d.ts:50-55 ; expo-symbols SymbolModule.types.d.ts (l.1-30 + grep), android/index.d.ts, symbols.json (grep) ; sf-symbols-typescript (grep) · web/Modules/Auth/lang/fr/mobile.php.

## Non couvert
- Code d'exécution d'expo-router (`getRoutes`, résolution des groupes, redirection de `Protected` vers l'ancre) : seules les `.d.ts` ont été lues, et la convention `(tabs)` n'est pas prouvée par la source lue.
- Composants de champ autres que TextField, FieldShell, OfflineScreen, LoginService, LogoutService, token-storage, pick-avatar, form-file, connectivity, LocaleService, MessageService, thème.
- `en/mobile.php` et `Modules/Form/lang/*` côté web ; tout le côté Laravel.
- Rendu visuel et accessibilité réelle de la barre d'onglets (simulateur).
- Commande exacte qui régénère les routes typées hors `expo start` (seul le watcher du serveur de dev a été vu).
