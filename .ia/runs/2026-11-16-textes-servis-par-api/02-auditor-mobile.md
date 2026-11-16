# 02 — Audit mobile (auditor) — textes servis par l'API

STATUT : TERMINÉ (lecture seule, aucun commit, aucune spec `.ia/memory/` écrite : le périmètre d'écriture de la tâche est limité à ce rapport, la spec est donc rendue ici)

Racine : `/Users/bertrandzang/home/private/yamo/mobile` (chemins ci-dessous relatifs à cette racine). Dernier commit mobile : `c023dc8`.

## 1. Textes de l'interface (i18n)

- `src/shared/i18n/messages.ts:13-62` : 22 clés × fr/en, placeholders `{name}`. `APP_LOCALES = ['fr','en']` (:7), `Locale` = union de ces codes (:8), `DEFAULT_LOCALE = 'fr'` (:11), `MessageKey = keyof messages.fr` (:64).
- `src/shared/i18n/locale.ts:30-32` `translate(locale, key, params)` : lit `messages[locale][key]` et remplace `/\{(\w+)\}/` (pas `:name`). Clé absente = impossible à la compilation (typage), plantage à l'exécution (`.replace` sur `undefined`).
- `locale.ts:16-28` `pickBestLocale(preferred, available, fallback)` : filtre `available` par `isLocale` (:17, donc seules fr/en sont retenues), parcourt l'appareil dans l'ordre, ramène `fr-CH`→`fr` (:20), sinon `fallback` s'il est fr/en, sinon `DEFAULT_LOCALE` (:27).
- `src/shared/i18n/I18nContext.tsx:15-18` : valeur **par défaut** du contexte = `DEFAULT_LOCALE` + `translate` local ; `useI18n()` (:20-22). Tout composant rendu hors `AuthProvider` (tests) reçoit les textes français locaux.
- `src/features/auth/types/index.ts:36` : `AuthContextValue.t` typé `MessageKey` ; `User.locale: Locale` (:10).

### Liste exhaustive des appels (22 clés, toutes utilisées)

| Clé | Appels (fichier:ligne) | Accès |
|---|---|---|
| signIn.title | features/auth/screens/SignInScreen.tsx:22 | useAuth().t (:16) |
| home.greeting | features/auth/components/UserSummary.tsx:14 (param name) | useAuth().t (:9) |
| home.signedInAs | UserSummary.tsx:16 (param fullName) | idem |
| home.email | UserSummary.tsx:18 (param email) | idem |
| home.signOut | features/auth/components/SignOutButton.tsx:24 | useAuth().t (:11) |
| home.profile | features/auth/components/ProfileLink.tsx:15 | useAuth().t (:11) |
| profile.back | features/auth/screens/ProfileScreen.tsx:37 | useAuth().t (:23) |
| profile.title | ProfileScreen.tsx:39 | idem |
| profile.saved | ProfileScreen.tsx:44 | idem |
| form.loading | features/form/components/FormLoader.tsx:29 (accessibilityLabel) | useI18n (:24) |
| form.submitting | features/form/components/DynamicForm.tsx:91 | useI18n (:29) |
| form.selectPlaceholder | features/form/components/fields/SelectField.tsx:31 | useI18n (:15) |
| form.showPassword / form.hidePassword | fields/TextField.tsx:62 (accessibilityLabel) | useI18n (:29) |
| avatar.label | features/auth/components/Avatar.tsx:33, :45 ; AvatarPicker.tsx:71 (titre Alert) | useI18n (Avatar:25) / useAuth (AvatarPicker:27) |
| avatar.change | AvatarPicker.tsx:79 (a11y), :91 | useAuth |
| avatar.choose / avatar.remove / avatar.cancel | AvatarPicker.tsx:64 / :66 / :68 (boutons Alert) | useAuth |
| avatar.uploading | AvatarPicker.tsx:91 | useAuth |
| error.network | DynamicForm.tsx:55 ; FormLoader.tsx:39 ; AvatarPicker.tsx:45 | — |
| error.unknown | DynamicForm.tsx:57 ; FormLoader.tsx:39 ; AvatarPicker.tsx:47 | — |

Seul autre consommateur direct de `translate` : `features/auth/providers/AuthProvider.tsx:102` (et le test `AvatarPicker.test.tsx:35`).

### Textes/glyphes affichés hors `messages.ts`
- `FormLoader.tsx:42` bouton réessayer = glyphe `↻`, **sans accessibilityLabel** (:41) — c'est le seul « Réessayer » existant.
- `ProfileScreen.tsx:37` préfixe `← ` ; marque d'obligation ` *` : `FieldShell.tsx:18`, `fields/CheckboxField.tsx:29`, `fields/SwitchField.tsx:16` ; coche `✓` : `CheckboxField.tsx:25`, `CheckboxGroupField.tsx:34`, `SelectField.tsx:53`.
- `app.json` `photosPermission` « yamo accède à vos photos… » (texte natif iOS, figé au build, ne peut pas venir de l'API).
- Messages développeur non affichés à l'utilisateur : `useAuth.ts:10`, `config.ts:8`, `FormField.tsx:45` (console.warn), `errors.ts:21` (`'Network request failed'`, jamais rendu : chaque `catch` traite `NetworkError` avant `error.message`).
- Textes venant déjà de l'API : `ApiError.message` / `errors` (DynamicForm.tsx:53, AvatarPicker.tsx:43), libellés/placeholder/help/options/submit des formulaires.

## 2. Démarrage et chargement

- `src/app/_layout.tsx:10` `SplashScreen.preventAutoHideAsync()` ; `RootNavigator` cache le splash quand `!isLoading` (:31-35) et rend `null` tant que `isLoading` (:37-39). Aucun indicateur ni texte aujourd'hui : c'est le splash natif qui reste.
- `_layout.tsx:43-49` `Stack.Protected` : `index`+`profile` si `user !== null`, `sign-in` si `user === null`.
- `AuthProvider.tsx:99` `isLoading = token === undefined || localesQuery.isPending || (token string && meQuery.isPending)`.
- Requêtes : `['locales']` (:42-47, `staleTime: Infinity`, `retry: 1`) ; `['auth','me', token]` (:52-57, `enabled` si jeton, pas de retry sur 401/403 sinon `failureCount < 2`).
- Langue : `available` = codes API sinon `APP_LOCALES` (:48) ; `deviceLocale = pickBestLocale(useLocales(), available, data.default ?? 'fr')` (:49-50) ; `locale = user?.locale ?? deviceLocale` (:67) ; `apiClient.setLocale(locale)` **appelé dans le corps du rendu** (:70), donc l'en-tête `Accept-Language` (`ApiClient.ts:65`) est à jour avant que les requêtes de la nouvelle clé partent.
- `src/shared/services/LocaleService.ts:11-13` `GET /v1/locales` → `{ data: {code,label}[], default }`.
- Ordre réel : jeton (SecureStore) et locales en parallèle → `/me` dès que le jeton est lu. Une requête des textes dépendrait de `locale`, donc de locales **et** de `/me` (si jeton) : chaîne séquentielle.

## 3. Changement de langue

- `ProfileScreen.tsx:26-30` `save` → `profileService.update` → `setUser` (`AuthProvider.tsx:92-95`, `setQueryData(['auth','me',token])`) → `locale` recalculée (:67) → `setLocale` (:70).
- Modèle déjà en place pour les formulaires : `useFormDefinition.ts:15` `queryKey: ['form', name, locale]` + remontage `key={`${name}-${locale}`}` (`FormLoader.tsx:48`).
- `AuthProvider.tsx:102` `t` est recréé à chaque rendu (closure sur `locale`), mémo `value` dépend de `locale` (:107).

## 4. Formulaires dynamiques

- Types `src/features/form/types/index.ts:21-36` : `FormFieldDefinition { name, type, label|null, placeholder|null, help|null, required, default, options }` ; `FormDefinition { name, fields, submit: {label} }` — **pas de `title`/`subtitle`**.
- `DynamicForm.tsx:64-94` : rend champs, message général (`form-message`, rouge, `alert`, :78-82), bouton `form-submit`. Rien au-dessus des champs. Gestion d'erreur :48-58 (ApiError → erreurs champ ou message général ; NetworkError → `error.network` ; autre → `error.unknown`).
- `FieldShell.tsx:22-31` : l'aide n'est rendue que si `!error` (:22) — **l'erreur remplace l'aide** (point à corriger par le contrat). Erreur rouge `accessibilityRole="alert"`, testID `${name}-error` (:28).
- `CheckboxField.tsx:15` et `SwitchField.tsx:12` passent `{...field, label: null}` à `FieldShell` **sans neutraliser `help`**, puis rendent eux-mêmes l'aide (`CheckboxField.tsx:32-36`, `SwitchField.tsx:26-30`) : avec un `help` non nul, l'aide s'afficherait **deux fois** (FieldShell:22 + composant). Constat de lecture, non vérifié à l'exécution.
- Bordure rouge : seulement via `useInputStyle` (`inputStyle.ts:15`) — TextField, NumberField, TextareaField, SelectField ; Radio/Checkbox/CheckboxGroup/Switch n'en ont pas.
- `fieldError` (`utils/values.ts:21-23`) : premier message de `name` ou `name.*`.
- `SignInScreen.tsx:22` affiche son propre titre `t('signIn.title')` au-dessus de `FormLoader name="auth.login"` (:23) : à remplacer par le titre du formulaire.

## 5. NetworkError

- Levée uniquement si `fetch` rejette (`ApiClient.ts:60-73`) ; aucun timeout.
- Traitée : `DynamicForm.tsx:54`, `FormLoader.tsx:39`, `AvatarPicker.tsx:44`. Le login (`AuthProvider.tsx:72-80`) et le profil (`ProfileScreen.tsx:26-30`) remontent l'erreur à `DynamicForm` via `onSubmit`.
- **Non traitée au démarrage** : locales en échec → repli silencieux sur `APP_LOCALES` (:48) ; `/me` en échec réseau → `meQuery.data` indéfini → `user = null` (:66) → l'app affiche **sign-in** alors que le jeton est conservé (seul 401/403 l'efface, :60-64). Aucun écran hors ligne n'existe.
- `signOut` avale l'erreur de logout (:85).

## Pièges pour l'implémentation

1. **Textes nécessaires avant le chargement** : l'écran hors ligne doit choisir fr/en sans `/locales` → `pickBestLocale(deviceLocales, APP_LOCALES)` suffit (`locale.ts:16`) ; seuls `error.network` + Réessayer doivent rester dans `messages.ts`. Le retour actuel `null` (`_layout.tsx:37`) ne peut pas porter d'indicateur sans rendre quelque chose avant `hideAsync`.
2. **Recharger la langue sans démonter l'app** : si la requête des textes (clé `['messages', locale]` par analogie avec `useFormDefinition.ts:15`) entre dans `isLoading`, un changement de langue au profil repasse `isLoading` à vrai → `_layout.tsx:37` rend `null` → la `Stack` et l'état de `ProfileScreen` (`saved`, :24) sont perdus. À trancher par l'implémenteur (données précédentes conservées pendant le rechargement, ou `isLoading` limité au premier chargement).
3. **Clé de requête liée à la langue ET au compte** : l'API répondra dans la langue du compte si jeton, sinon `Accept-Language` (contrat). La clé `locale` couvre les deux car `locale = user?.locale ?? deviceLocale`.
4. **Erreur réseau au démarrage** à distinguer : locales (repli silencieux aujourd'hui), `/me` (bascule vers sign-in aujourd'hui), textes (à créer). Retries React Query par défaut (useFormDefinition sans option `retry`) allongent l'attente avant l'écran hors ligne.
5. **Typage** : `t` est typé `MessageKey` (types/index.ts:36, I18nContext.tsx:8) ; le contrat garde la liste des clés dans `messages.ts`. `translate` doit passer de `{name}` à `:name` (`locale.ts:31`) et ne plus planter sur clé absente (renvoyer la clé).
6. **Contexte par défaut** (`I18nContext.tsx:15-18`) : traduit localement en fr ; il devra fournir autre chose une fois les textes retirés (tests et rendu hors provider).
7. **`Locale` limité à fr/en** (`messages.ts:7-8`, `locale.ts:17`) : la justification « langue dont l'app a les textes » change ; le contrat garde fr/en pour le hors ligne — à décider si le filtre reste.

## Tests qui supposent des textes français en dur

- `src/features/form/__tests__/DynamicForm.test.tsx:123` `'Afficher le mot de passe'`, `:127` `'Masquer le mot de passe'` — viennent du **contexte par défaut** (pas de provider dans le test). Les autres textes (`Envoyer`, `Pseudonyme`, `Visible par tous`, erreurs 422, `Too Many Attempts.`) viennent des fixtures/ApiError : non affectés. Fixture `everyType` (:18-34) sans `title`/`subtitle` ; aucun test ne vérifie l'aide **et** l'erreur ensemble (:97 vérifie l'erreur seule ; :48 l'aide seule).
- `src/features/auth/__tests__/AvatarPicker.test.tsx:5,35` utilise `translate('fr', …)` et attend `'Choisir une photo'`, `'Annuler'` (:73), `'Choisir une photo'` (:81, :91, :114), `'Retirer la photo'` (:102).
- `src/shared/i18n/__tests__/locale.test.ts:33-37` `translate('fr'|'en','home.greeting',{name})` → `'Bonjour jeanne'`/`'Hello jeanne'` (clé qui quittera `messages.ts`, syntaxe `{name}`). `pickBestLocale` (:3-31) non affecté.
- `src/shared/api/__tests__/ApiClient.test.ts` : aucun texte d'interface (en-tête `Accept-Language` :31).

## Fichiers lus (intégralement)

src/shared/i18n/{messages.ts,locale.ts,I18nContext.tsx,__tests__/locale.test.ts} ; src/shared/services/LocaleService.ts ; src/shared/api/{ApiClient.ts,errors.ts,__tests__/ApiClient.test.ts} ; src/shared/config.ts ; src/app/{_layout,index,profile,sign-in}.tsx ; src/features/auth/{providers/AuthProvider.tsx,context/AuthContext.ts,hooks/useAuth.ts,types/index.ts,services/ProfileService.ts,utils/pick-avatar.ts,utils/device-name.ts,screens/*.tsx,components/*.tsx,__tests__/AvatarPicker.test.tsx} ; src/features/form/{components/*.tsx,components/fields/*,types/index.ts,hooks/useFormDefinition.ts,services/FormService.ts,utils/values.ts,__tests__/DynamicForm.test.tsx} ; package.json ; app.json (début).

## Ce que l'audit ne couvre pas

- Rien n'a été exécuté (ni tests, ni app) : le doublon d'aide Checkbox/Switch et le passage à sign-in hors ligne sont des constats de lecture.
- Comportement de `fetch` quand DDEV est arrêté (rejet immédiat ou attente) : non vérifié, aucun timeout dans le client.
- Sémantique exacte de `failureCount` dans la fonction `retry` de React Query v5 (nombre réel de tentatives de `/me`) : non vérifiée.
- Non lus : `LoginService.ts`, `LogoutService.ts`, `token-storage.ts`, `form-file.ts` (+ test), thème (`themed-text`, `theme.ts`), `README.md`, `AGENTS.md` du mobile, côté web/API.
- Aucune spec `spec_*.json` écrite dans `.ia/memory/` (hors périmètre d'écriture de la tâche).
