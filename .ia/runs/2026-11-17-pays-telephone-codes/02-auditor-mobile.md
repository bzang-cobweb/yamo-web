# 02 — Audit mobile (Expo) avant le chantier pays, téléphone et codes

STATUT : TERMINÉ (lecture seule ; aucun fichier du projet modifié ; rien de commité)
Racine : `/Users/bertrandzang/home/private/yamo/mobile/src` (chemins ci-dessous relatifs à `src/`). HEAD mobile : `41d3843`.
Stack : Expo SDK 58, expo-router ~58, React 19.3, RN 0.88, react-query 5, jest-expo 30 (`../package.json`). Scripts : `lint`, `typecheck`, `test`.

## 1. Feature form

### Types — `features/form/types/index.ts`
- `FieldType` (6-19) : text, hidden, email, password, number, textarea, select, radio, checkbox, checkbox_group, switch, date. Pas de `phone`, `code`.
- `FieldOption = { value; label }` (21) : **pas de parent**.
- `FormFieldDefinition` (23-32) : name, type, label|null, placeholder|null, help|null, required, default: unknown, options. **Pas de `readonly` ni `depends_on`.**
- `FormDefinition` (34-42) : name, title, subtitle, fields, submit.label.
- `FieldValue = string | boolean | string[]` (47) ; `FormValues = Record<string, FieldValue>` (49) ; `FormErrors = Record<string,string[]>` (52).
- `FieldProps<V>` (55-63) : field, value, onChange(value: V), error?, disabled?, onSubmitEditing?. **Un champ ne peut changer que sa propre valeur.**

### FormField — `components/FormField.tsx`
- Switch sur `field.type` (23-52) ; text/hidden/email/password → `TextField` (24-28, seul à recevoir `onSubmitEditing`) ; select → `SelectField` (33-34)… Coercition : `asText`/`asBoolean`/`asList` (19-21).
- `default` exhaustif `const unknown: never = field.type` (45-51) : ajouter un `FieldType` sans `case` casse `tsc`. Mode d'emploi en commentaire : « un cas ici et son composant dans `fields/` » (12-16).

### DynamicForm — `components/DynamicForm.tsx`
- Props : `definition`, `onSubmit(values) => Promise<void>` uniquement (14-18). Aucune prop de valeurs initiales ni de champs ajoutés.
- Valeurs initiales : `useState(() => initialValues(definition))` (30), donc **calculées au montage seulement**. Pour repartir de zéro, il faut changer la `key` (25-26).
- `change(name, value)` (35-38) : remplace **une seule clé** et efface l'erreur de cette clé seulement.
- `submit` (40-62) : `onSubmit(values)` envoie **toutes** les valeurs, champs cachés compris (46). Sur `ApiError` : `setErrors(error.errors)` (50). Si aucune clé (`key.split('.')[0]`) n'est le nom d'un champ de la définition, `message` général = premier message ou `error.message` (52-53). Sur `NetworkError` : `offline.message` (55). Sinon : `error.unknown` (57).
- Rendu : un `FormField` par champ, `disabled={submitting}` commun à tous (81-91) ; erreur via `fieldError(errors, field.name)` (87) ; bouton `form-submit` (99-107).

### FormLoader — `components/FormLoader.tsx`
- Props : `name`, `onSubmit` seulement (13-17). Charge via `useFormDefinition(name)` (25). Chargement, erreur et réessai : 27-49. Rendu : `<DynamicForm key={`${name}-${locale}`} …/>` (51), donc **un changement de langue remonte le formulaire et perd la saisie**.
- `useFormDefinition` : `queryKey ['form', name, locale]`, `gcTime: 0` (`hooks/useFormDefinition.ts:19-23`). `FormService.get(name)` : `GET /v1/forms/{name}` **sans paramètre de requête** (`services/FormService.ts:10-12`).

### FieldShell — `components/FieldShell.tsx`
- Libellé, avec ` *` si `required` (19-24) ; enfant ; aide `${name}-help` (26-30) ; erreur `${name}-error`, role alert (31-35). `ERROR_COLOR` est exporté (9).

### fields/*
- `TextField` (`fields/TextField.tsx`) : `VARIANTS: Partial<Record<FieldType, TextInputProps>>`, avec text, email (keyboardType email-address, autoComplete email, textContentType emailAddress) et password (13-17). Type `hidden` → `return null` (33-35). `editable={!disabled}` (47) ; variante étalée en dernier (50). Bouton œil du mot de passe **sans `disabled`** (59-66).
- `SelectField` (`fields/SelectField.tsx`, détail) :
  - `selected = field.options.find(o => o.value === value)` (18) ;
  - déclencheur `Pressable` `field-${name}` : `accessibilityValue {text: selected?.label}`, `disabled={disabled}`, ouvre la liste (22-33) ;
  - texte affiché : `selected?.label ?? placeholder ?? t('form.selectPlaceholder')` (31) ;
  - `Modal` transparent, en fondu (35) ; le fond est un `Pressable` qui ferme (36), et la feuille `ThemedView`, `maxHeight 70%` (37, 67), est **dans** ce Pressable ;
  - libellé dans la feuille (38) ;
  - `ScrollView` qui liste **toutes** les options (39-57), sans recherche ni filtre ;
  - option `field-${name}-option-${value}`, `accessibilityRole menuitem` : appuyer appelle `onChange` puis ferme (41-49) ; une coche marque la valeur choisie (53).
- `RadioField` : options visibles, `field-${name}-option-${v}` (17-35). `CheckboxField` / `SwitchField` : libellé à côté, `FieldShell` avec `label: null` (`CheckboxField.tsx:15`, `SwitchField.tsx:12`). `CheckboxGroupField` : liste, avec bascule (14-16). `NumberField` : `decimal-pad` (20). `TextareaField` : multiline (20). `DateField` : `AAAA-MM-JJ`, avec « Effacer » si le champ est facultatif (59-72).
- `inputStyle.ts` : `useInputStyle(hasError)` (9-17), bordure rouge en cas d'erreur.

### utils/values — `utils/values.ts`
- `initialValue` (4-14) : checkbox et switch → `default === true` ; checkbox_group → tableau de chaînes ; sinon `null|undefined` → `''`, sinon `String(default)` (12). **Un défaut objet devient `"[object Object]"`.**
- `initialValues(definition)` (16-18).
- `fieldError(errors, name)` (21-23) : `errors[name][0]`, sinon la première clé qui commence par `${name}.`.

### Comment ajouter les nouvelles capacités (ce que le code impose)
- **`readonly`** : l'ajouter à `FormFieldDefinition` (types:23-32).
  - Affichage non modifiable : passer `disabled={submitting || field.readonly}` (DynamicForm:88). Chaque champ respecte déjà `disabled` (TextField:47, SelectField:27, DateField:51/64…), sauf le bouton œil (TextField:59-66).
  - Non-envoi : filtrer dans `submit` avant `onSubmit(values)` (DynamicForm:46). Aujourd'hui, toutes les valeurs partent.
  - Valeur affichée : vient de `default` (values.ts:12).
- **`depends_on`** : ajouter `depends_on?: string|null` au champ et `parent?: string|null` à `FieldOption` (types:21).
  - Filtrage : dans `SelectField`, les options listées (40) et le `selected` (18) doivent ne retenir que celles dont `parent` vaut la valeur du parent. `SelectField` ne reçoit pas les autres valeurs : il faut passer la valeur parente depuis `FormField` ou `DynamicForm` (FieldProps:55-63).
  - Vider l'enfant : `change()` (DynamicForm:35-38) ne touche qu'une clé. Il faut y mettre à `''` les champs dont `depends_on === name`.
- **Type `phone`** :
  - nouveau `FieldType` (types:6-19), un `case` (FormField:23-51, imposé par `never`) et un composant `fields/PhoneField`, sélecteur de pays et `TextInput` sur une ligne ;
  - deux clés `<name>` et `<name>_country`, mais `onChange` ne porte qu'une valeur (FieldProps:58) et `change()` qu'une clé. Options : un `FieldValue` objet, puis aplatir dans `submit`, ou un `onChange` qui accepte des clés voisines. Dans les deux cas, `initialValue` (values.ts:4-14) doit initialiser `<name>_country` (pays du profil prérempli : source `default` ou autre, à définir côté API) ;
  - erreurs : `fieldError` ne voit pas `phone_country` (préfixe `phone.` seulement, values.ts:22), et `onFields` (DynamicForm:52) ne connaît pas `phone_country` : **une erreur seule sur `phone_country` irait au message général, et pas sous le champ** ;
  - sélecteur de pays : la liste d'environ 245 pays dans le `ScrollView` de SelectField (39-57) n'a ni recherche ni virtualisation.
- **Type `code`** : un `case 'code'` vers `TextField`, avec une entrée `VARIANTS.code` (TextField:13-17), par exemple `keyboardType 'number-pad'`, `textContentType 'oneTimeCode'`, `autoComplete 'one-time-code'`, `maxLength 6`. Il faut aussi l'ajouter à la liste des types qui reçoivent `onSubmitEditing` (FormField:24-28).

## 2. Feature auth

- `User` (`features/auth/types/index.ts:4-20`) : id, name, first_name, last_name, full_name, initials, gender, birth_date, **`email: string`** (16, non nullable), locale, avatar_url. Pas de country, region, phone, last_seen_at, is_online.
- `LoginCredentials = Record<string, unknown> & { email?; password? }` (26-29) ; `LoginResponse = { data: User; token }` (32) ; `AuthContextValue` (37-54), avec `signIn(credentials)`, `signOut`, `setUser`.
- `AuthProvider` (`providers/AuthProvider.tsx`) :
  - `token` vaut `undefined`, `null` ou une chaîne (48) ; `applyToken` règle `apiClient.setToken` et l'état (50-53) ; lecture au montage (55-57) ;
  - `meQuery` : clé `['auth','me', token]`, `profileService.get()`, active si un jeton existe (70-76) ; jeton rejeté (401/403) → `clearToken` + `null` (79-83) ;
  - `user = meQuery.data` si jeton, sinon `null` (85) ; `locale = user.locale ?? appareil` (86) ; `apiClient.setLocale` à chaque rendu (89) ;
  - `signIn` (115-123) : `loginService.login(credentials, deviceName())`, puis `saveToken`, puis `setQueryData([...ME_KEY,newToken], user)`, puis `applyToken`. **Il suppose que login rend aussitôt `{user, token}`.** `signOut` : 125-133. `setUser` : `setQueryData([...ME_KEY, token])` (135-138).
- Services (une méthode par appel, client injectable dans le constructeur) :
  - `LoginService.login` : `POST /v1/auth/login` avec `{...credentials, device_name}`, renvoie `{user: data, token}` (`services/LoginService.ts:13-20`) ;
  - `LogoutService.logout` : `POST /v1/auth/logout` (8-10) ;
  - `ProfileService` : `get` `GET /v1/auth/me` (15-17), `uploadAvatar` multipart (24-29), `removeAvatar` (32-34) ;
  - `PersonalInfoService.update` : `PATCH /v1/auth/me/personal-info`, renvoie `User` (10-13 ; la doc dit encore « prénom et nom ») ;
  - `PasswordService.update` : `PUT /v1/auth/me/password` (8-11) ;
  - `PreferencesService.update` : `PATCH /v1/auth/me/preferences` (10-13).
- `ApiClient` (`shared/api/ApiClient.ts`) : en-têtes Accept, Accept-Language et Bearer (67-72), **aucun en-tête personnalisé**, donc un jeton d'étape passe dans le corps. Erreur : `ApiError(status, payload.message, payload.errors)` (85-87). `ApiError.rejectsToken` : 401/403 (`shared/api/errors.ts:13-15`).
- `SignInScreen` (`screens/SignInScreen.tsx:14-26`) : `<FormLoader name="auth.login" onSubmit={signIn} />` dans KeyboardAvoidingView. Aucun lien et aucun état local.
- Routes (`app/`) :
  - `_layout.tsx` : QueryClient, puis AuthProvider, puis ThemeProvider (19-27) ; `RootNavigator` (36-60) : `Stack` affiché si `!isLoading`, `Stack.Protected guard={user!==null}` → `(tabs)` (50-52), `guard={user===null}` → `sign-in` (53-55) ;
  - **toute nouvelle route invitée (`sign-up`, code) doit être déclarée dans le second `Stack.Protected`** ;
  - `sign-in.tsx` réexporte l'écran (1-4) ; `(tabs)/_layout.tsx` : onglets index et profile, avec `popToTopOnBlur` (37-38) ; `(tabs)/profile/_layout.tsx` : `Stack` sans en-tête (4-6) ; `profile/{index,personal,password,preferences}.tsx` réexportent les écrans.
- `ProfileScreen` (`screens/ProfileScreen.tsx`) :
  - `notice` vient de `useLocalSearchParams` (23) ; `password` → `profile.passwordSaved`, `saved` → `profile.saved` (24) ; effacé au blur (27) ;
  - **`{user.email}` affiché sans condition** (43-45) ;
  - liens `ProfileMenuLink` (52-56 ; composant `components/ProfileMenuLink.tsx:12-30`, `router.push(href)`).
- `ProfileFormScreen` (`screens/ProfileFormScreen.tsx`) : `ProfileNotice = 'saved'|'password'` (14) ; props `form`, `onSubmit`, `notice` (16-22) ; `submit` = `await onSubmit(values)`, puis `router.dismissTo({ pathname: '/profile', params: { notice } })` (31-34). Bouton retour : `router.back()` (41). Un seul `FormLoader` (44).
- `PersonalInfoScreen` / `PreferencesScreen` : `setUser(await service.update(values))` (`PersonalInfoScreen.tsx:13`). `PasswordScreen` : sans `setUser` (`PasswordScreen.tsx:6`).

## 3. Enchaîner deux formulaires (identifiants puis code)

Constat : `FormLoader` et `DynamicForm` n'acceptent ni valeurs initiales ni champs ajoutés (FormLoader:13-17, DynamicForm:14-18). La définition vient de `GET /v1/forms/{name}` sans contexte (FormService:10-12), donc l'API ne peut pas y injecter le jeton d'étape.

- **État à garder** : dans l'écran (par ex. `useState` dans `SignInScreen`), le jeton d'étape et, pour l'affichage, l'identifiant et le canal. Pas dans DynamicForm : il est remonté à chaque changement de langue (FormLoader:51), alors que l'écran ne l'est pas.
- **Ajout du jeton à l'envoi** : sans toucher DynamicForm, l'`onSubmit` de l'écran fusionne `{...values, step_token}`. C'est le précédent de `LoginService`, qui ajoute `device_name` (LoginService.ts:14-17).
- **Valeurs initiales depuis l'étape précédente** (lecture seule de l'e-mail actuel, par exemple) : ajouter une prop `initialValues?: FormValues`, que FormLoader transmet et que DynamicForm fusionne au montage (30). Elle doit entrer dans la `key` (FormLoader:51) si elle peut changer.
- **Passage d'étape** : rendre `<FormLoader name="auth.login.code" …/>` à la place du premier. Le `name` différent change déjà la `key`, donc l'état repart à zéro.
- **Fin de connexion** : `signIn` (AuthProvider:115-123) doit être scindé. L'étape 1 appelle le service et ne touche pas au jeton. L'étape 2 reçoit `{user, token}` et applique le bloc 118-120 (une méthode du contexte, par ex. `completeSignIn`, à ajouter dans `AuthContextValue`, types:37-54).
- **Erreurs d'étape** : une 422 sur `step_token` ne vise aucun champ affiché, donc message général (DynamicForm:52-53). Mais si `step_token` est un champ `hidden` de la définition, il compte dans `fieldNames` : **l'erreur n'est affichée nulle part**, puisque TextField renvoie `null` (TextField:33-35).
- **Profil (changer d'e-mail ou de téléphone)** : `ProfileFormScreen` fait `dismissTo` dès que le premier `onSubmit` réussit (31-34). Pour deux étapes, il faut soit un écran dédié, soit un `onSubmit` qui ne revient au profil qu'à l'étape du code. Il faut aussi un nouveau `ProfileNotice` et son message (ProfileScreen:24).

## 4. i18n
- `ApiMessageKey`, union fermée (`shared/i18n/messages.ts:34-59`) ; `MessageKey = ApiMessageKey | OfflineKey` (61). Chaque nouveau texte (liens Ajouter/Changer, notices, « Créer un compte », renvoi du code…) doit y être ajouté, sinon `t()` refuse au typage.
- `translate` : une clé absente s'affiche telle quelle (`shared/i18n/locale.ts:42`).
- `TEST_MESSAGES`, copie de `lang/fr/mobile.php` (`shared/i18n/test-messages.ts:9-35`), à tenir synchronisée. `TEST_I18N` : 37-40.

## 5. Tests existants touchés
- `features/form/__tests__/DynamicForm.test.tsx` :
  - helper `field()` (12-14) : si `readonly`/`depends_on` deviennent obligatoires dans le type, ajouter les défauts ici ;
  - `everyType` (22-41) ; l'attendu d'envoi exact (90-103) change si un champ readonly ou phone y est ajouté ;
  - test 422 (106-121) ; message général (123-132).
- `features/form/__tests__/DateField.test.tsx:21-30` : `FormFieldDefinition` littéral complet, qui casse au typage si un champ obligatoire est ajouté.
- `features/auth/__tests__/ProfileScreen.test.tsx:19-31` et `AvatarPicker.test.tsx:14-26` : fixture `User` typée, à compléter (country, region, phone, is_online…) ; l'attendu `profile-email` (`ProfileScreen.test.tsx:64`) ; les trois liens (65-67).
- `features/auth/__tests__/ProfileFormScreen.test.tsx:12,45` : mocke `router.dismissTo` et attend `{pathname:'/profile', params:{notice}}`.
- `features/auth/__tests__/profile-services.test.ts:19-41` : modèle de test des nouveaux services (faux client injecté).
- Aucun test de `SignInScreen`, `AuthProvider`, `LoginService`, `SelectField` ni `FormLoader`.

## 6. Pièges (récapitulatif)
1. DynamicForm envoie tout, cachés compris (46) : `readonly` doit être filtré explicitement.
2. `change()` ne touche qu'une clé (35-38) : la dépendance des listes et le téléphone sur deux clés exigent de le modifier.
3. `fieldError` et `onFields` ne reconnaissent que `name` ou `name.*` (values.ts:22, DynamicForm:52) : `phone_country` passe au message général ou se perd.
4. Une erreur sur un champ `hidden` n'est jamais visible (DynamicForm:52 + TextField:33-35).
5. `String(default)` d'un objet donne `"[object Object]"` (values.ts:12).
6. L'exhaustivité `never` (FormField:47) impose un `case` par nouveau type.
7. La `key` `${name}-${locale}` (FormLoader:51) perd la saisie au changement de langue : l'état d'étape doit vivre dans l'écran.
8. `signIn` pose le jeton immédiatement (AuthProvider:117-120) : la connexion en deux étapes impose de le scinder.
9. `User.email: string` (types:16) et un affichage sans condition (ProfileScreen:44) : à passer en `string | null`.
10. Nouvelles routes invitées : à déclarer dans `Stack.Protected guard={user===null}` (_layout:53-55), sinon elles sont inaccessibles.
11. SelectField : liste complète sans recherche (39-57) ; la feuille est dans le `Pressable` du fond (36-37).
12. Le bouton œil reste actif quand le champ est `disabled` (TextField:59-66).
13. `ProfileFormScreen` revient au profil dès le premier envoi réussi (31-34).
14. `ApiClient` n'a pas d'en-tête personnalisé (67-72) : le jeton d'étape passe dans le corps.

## Fichiers lus (en entier)
- Feature form : `features/form/` types, FormField, DynamicForm, FieldShell, FormLoader, `fields/*` (9 composants et inputStyle), utils/values, hooks/useFormDefinition, services/FormService, `__tests__/DynamicForm.test.tsx`.
- Feature auth : `features/auth/` types, context, hooks/useAuth, providers/AuthProvider, services/* (6), utils/token-storage, utils/device-name, écrans (SignIn, Profile, ProfileForm, PersonalInfo, Password, Preferences, Home), ProfileMenuLink, SignOutButton, tests (ProfileFormScreen, ProfileScreen, profile-services).
- Routes et partagé : `app/` (8 routes et layouts), `shared/i18n/{messages,test-messages,locale,I18nContext}`, `shared/api/{ApiClient,errors}`, `../package.json`, `../AGENTS.md` (début).
- Lus en partie : `DateField.test.tsx` (1-40), `AvatarPicker.test.tsx` (1-50).

## Non couvert
- Non lus : `AvatarPicker.tsx`, `Avatar.tsx`, `pick-avatar.ts`, `form-file.ts`, `utils/dates.ts`, `ConnectivityGate`, `OfflineScreen`, `network/connectivity.ts`, `LocaleService`, `MessageService`, le thème, ainsi que les tests associés.
- Non vérifiés à l'exécution : le comportement tactile et le défilement du Modal de SelectField, et le remplissage automatique `oneTimeCode` (aucun test, aucun simulateur lancé).
- Le contrat API et `openapi.json` côté web ne sont pas lus : le format des futures définitions (`readonly`, `depends_on`, options avec parent, valeur par défaut d'un `phone`) reste à fixer par la tâche web.
- Pas de spec `spec_*.json` produite : le prompt limitait l'écriture à ce rapport.
