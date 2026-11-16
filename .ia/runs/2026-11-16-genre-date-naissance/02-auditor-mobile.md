# 02 — Audit mobile (formulaire, avatar/profil, i18n, mocks)

STATUT : TERMINÉ (lecture seule, rien installé, rien commité)
Racine : /Users/bertrandzang/home/private/yamo/mobile (chemins ci-dessous relatifs à `src/`)

## 1. Formulaire dynamique (`features/form`)

- `FieldType` = text | hidden | email | password | number | textarea | select | radio | checkbox | checkbox_group | switch — pas de `date` (types/index.ts:6-17).
- `FormFieldDefinition` = { name, type, label|null, placeholder|null, help|null, required: boolean, default: unknown, options: FieldOption[] } (types/index.ts:21-30).
- `FieldValue = string | boolean | string[]` — **pas de `null`** (types/index.ts:45). `FormValues = Record<string, FieldValue>` (:47).
- `FieldProps<V>` = { field, value: V, onChange(V), error?, disabled?, onSubmitEditing? } (types/index.ts:53-61).
- `FormField.tsx` : conversions `asText` (string sinon ''), `asBoolean`, `asList` (FormField.tsx:18-20) ; `switch(field.type)` un cas par type (:22-41) ; `default` exhaustif `const unknown: never = field.type` + `console.warn` + `null` (:42-48) → ajouter `'date'` à `FieldType` sans son `case` casse le typecheck. Commentaire : « Ajouter un type : un cas ici et son composant dans `fields/` » (:13-14).
- `FieldShell` : libellé `smallBold` avec `nativeID={name}-label`, astérisque rouge si `field.required` (FieldShell.tsx:19-24) ; aide `testID={name}-help` (:26-30) ; erreur `testID={name}-error`, `accessibilityRole="alert"` (:31-35). `ERROR_COLOR = '#d93025'` (:9).
- `RadioField` : `View accessibilityRole="radiogroup"` + `accessibilityLabel={label ?? name}` (RadioField.tsx:16) ; un `Pressable` par option, `testID=field-{name}-option-{value}`, `accessibilityRole="radio"`, `accessibilityState={{checked, disabled}}`, `onPress → onChange(option.value)` (:21-28) ; cercle 20 px + point 10 px couleur `theme.text` (:29-31, :44-45). Aucun `testID` `field-{name}` sur le groupe. Pas de désélection possible. Utilisable tel quel pour `gender` (contrat : radio existant).
- `SelectField` (modèle le plus proche d'un DateField) : `Pressable` `testID=field-{name}`, `accessibilityRole="button"`, `accessibilityValue={{ text: selected?.label }}`, `style={inputStyle}` (SelectField.tsx:22-29) ; texte affiché = label choisi, sinon placeholder, sinon `t('form.selectPlaceholder')`, couleur `textSecondary` si vide (:30-32) ; `Modal transparent animationType="fade"`, fond cliquable qui ferme (:35-36) ; feuille `ThemedView type="backgroundElement"` (:37) ; options `testID=field-{name}-option-{value}`, `accessibilityRole="menuitem"` (:41-50). État ouvert local `useState(false)` (:17).
- `useInputStyle(hasError)` : retourne un tableau de styles (radius 8, bordure 1 transparente, padding `Spacing.three`/`two`, fontSize 16) + couleurs du thème + bordure `ERROR_COLOR` si erreur (inputStyle.ts:9-28).
- `initialValue` : checkbox/switch → `default === true` ; checkbox_group → tableau en String ; **défaut : `null`/`undefined` → `''`, sinon `String(default)`** (utils/values.ts:4-14). Un `date` sans `case` dédié tomberait dans ce défaut : `null` → `''`, `'1990-05-12'` → `'1990-05-12'`.
- `fieldError` : premier message de `errors[name]`, sinon d'une clé `name.*` (values.ts:21-23).
- `DynamicForm` : valeurs initiales via `initialValues` (DynamicForm.tsx:30) ; `change` remplace la valeur et efface l'erreur du champ (:35-38) ; `submit` appelle `onSubmit(values)` **avec toutes les valeurs telles quelles, sans filtrage ni conversion** (:46) ; 422 → erreurs par champ, message général si aucune clé ne vise un champ (:48-53) ; `NetworkError` → `offline.message` (:54-55) ; sinon `error.unknown` (:56-57). `disabled={submitting}` sur chaque champ (:88).
- Chaîne d'envoi : `PersonalInfoScreen` → `personalInfoService.update(values)` (PersonalInfoScreen.tsx:13) → `client.patch('/v1/auth/me/personal-info', values)` (PersonalInfoService.ts:11-13) → `JSON.stringify(body)` (shared/api/ApiClient.ts:73). Donc un champ vide part en **`""`**, jamais `null` — confirmé par le test (DynamicForm.test.tsx:87-99 : `email: ''`, `age: ''`, `bio: ''`).
- Commentaire obsolète à mettre à jour : « prénom et nom » (PersonalInfoService.ts:10, PersonalInfoScreen.tsx:5).

## 2. Tests du formulaire (`features/form/__tests__/DynamicForm.test.tsx`)

- Fabrique `field(overrides)` : défauts `label=name, placeholder null, help null, required false, default null, options []` (:10-12).
- Fixture `everyType` : 11 champs, un par type (:20-38). Ajouter `date` = ajouter une ligne ici ET une clé dans l'objet attendu de l'envoi (:87-99), sinon `toHaveBeenCalledWith` échoue.
- Test « un composant par type » parcourt une liste de `testID` (:53-58).
- Rendu enveloppé dans `I18nContext.Provider value={TEST_I18N}` (:41-47).

## 3. Nom complet, initiales, Avatar, User

- `User` = { id, name, first_name, last_name, email, locale, avatar_url } — ni `full_name`, `initials`, `gender`, `birth_date` (features/auth/types/index.ts:4-13).
- `initialsOf` (exporté) : 1re lettre de `first_name.trim()` + `last_name.trim()`, sinon `name.charAt(0)`, `toUpperCase()` (components/Avatar.tsx:17-21). Accents conservés (pas de normalisation).
- `Avatar` props : `user: Pick<User, 'name'|'first_name'|'last_name'|'avatar_url'>` (Avatar.tsx:11) ; photo si `avatar_url` (`testID="avatar-image"`, :29-39), sinon `View testID="avatar-initials"` affichant `initialsOf(user)` (:42-48).
- Usages d'`Avatar` : `HomeScreen` `<Avatar user={user} />` (screens/HomeScreen.tsx:22) ; `AvatarPicker` `<Avatar user={user} size={96} />` (components/AvatarPicker.tsx:83, SIZE :20). `HomeScreen` salue avec `user.name` (pseudonyme), pas le nom complet (HomeScreen.tsx:24).
- Concaténation locale : `ProfileScreen` `${user.first_name} ${user.last_name}`.trim()` dans `testID="profile-full-name"` (screens/ProfileScreen.tsx:40-42). Seule occurrence dans `src` (grep `first_name|last_name|initialsOf`).
- Tests touchés :
  - `AvatarPicker.test.tsx` : importe `initialsOf` (:8) ; fixture `jeanne: User` (:15-23) ; attend `avatar-initials` = `'JD'` (:74) ; bloc `describe('initialsOf')` (:126-131) à supprimer avec la fonction.
  - `ProfileScreen.test.tsx` : fixture `jeanne: User` (:19-27) ; attend `profile-full-name` = `'Jeanne Dupont'` (:59).
  - Les deux fixtures sont typées `User` : ajouter des champs obligatoires au type les fait échouer au typecheck tant qu'elles ne sont pas complétées.
  - `ProfileFormScreen.test.tsx` mocke `FormLoader` (:15-25) — non impacté par le type `date`.

## 4. i18n

- `ApiMessageKey` : union fermée de 24 clés (shared/i18n/messages.ts:34-58) ; aucune clé `form.clear`/date. Un texte « Effacer » servi par l'API impose d'ajouter la clé ici, dans `TEST_MESSAGES` et côté Laravel (`mobile.php`).
- `TEST_MESSAGES` : « copie de `lang/fr/mobile.php` côté Laravel » (test-messages.ts:5-9) ; `TEST_I18N.locale = 'fr'` (:36-39).
- `translate` : clé manquante affichée telle quelle (locale.ts:31-44).
- Langue de l'app : `useI18n()` → `{ locale: 'fr'|'en', t }` (I18nContext.tsx:6-9, 22-24) ; `Locale` = `'fr' | 'en'` (messages.ts:10-11). Source de la langue : `user?.locale ?? deviceLocale` (providers/AuthProvider.tsx:86). Pour formater : `useI18n().locale`.
- Aucune utilisation de `Intl`, `toLocaleDateString` ou `DateTimeFormat` dans `src` (grep vide).

## 5. Mocks jest-expo

- Preset `jest-expo`, seul `moduleNameMapper` pour `.css` (package.json `jest`) ; dossier `jest/` ne contient que `style-mock.js` ; pas de `setupFiles`, pas de `__mocks__`.
- Modèle : `jest.mock('<module>', () => ({ export: jest.fn() }))` en tête de fichier de test :
  - `jest.mock('expo-image-picker', () => ({ launchImageLibraryAsync: jest.fn() }))` (AvatarPicker.test.tsx:13, ProfileScreen.test.tsx:10) — mocké parce qu'`AvatarPicker` importe `pick-avatar.ts` qui importe le module (utils/pick-avatar.ts:1).
  - `jest.mock('expo-network', () => ({ addNetworkStateListener: jest.fn(), useNetworkState: jest.fn() }))` (shared/network/__tests__/connectivity.test.ts:3).
  - Composant remplacé par un faux en JSX via `jest.requireActual('react-native')` (ProfileFormScreen.test.tsx:15-25) — modèle utilisable pour remplacer le composant `DateTimePicker` par un `Pressable` qui appelle `onChange`.
- Conséquence : si `DateField` est servi par `FormField`, **tout test qui rend `DynamicForm`** (DynamicForm.test.tsx) charge le module natif → mock à prévoir dans ce fichier (ou `setupFiles`, inexistant aujourd'hui).

## 6. Pièges

1. **Valeur vide** : `FieldValue` n'admet pas `null` (types/index.ts:45) et `initialValue` donne `''` pour un défaut `null` (values.ts:12) ; le formulaire envoie `''` (DynamicForm.tsx:46, test :87-99). Côté web, aucune désactivation de `ConvertEmptyStringsToNull` trouvée (grep vide dans web/bootstrap, web/app, web/Modules) — le `''` devrait donc arriver en `null` avec la règle `nullable` : **à vérifier par la tâche web, non prouvé ici**. Le bouton « Effacer » doit remettre `''` (cohérent avec les autres champs) ; introduire `null` obligerait à élargir `FieldValue`.
2. **Fuseau horaire** (connaissance JS générale, rien dans la source) : `new Date('1990-05-12')` est interprété en UTC → le jour peut glisser en fuseau négatif ; `date.toISOString().slice(0,10)` d'une date à minuit local glisse au jour précédent en Suisse (UTC+1/+2). Construire `new Date(y, m-1, d)` et sérialiser avec `getFullYear/getMonth/getDate` (zéro-padding).
3. **Affichage du picker** : comportement iOS (`display` `default`/`spinner`/`compact`/`inline`) et Android (dialogue impératif `DateTimePickerAndroid.open` ou composant qui se ferme seul, `onChange` avec `event.type === 'dismissed'`) : **non vérifiable** — la dépendance n'est pas installée (`node_modules/@react-native-community` absent). À relire dans la doc de la version installée par `npx expo install`.
4. **Limite « aujourd'hui »** : `maximumDate` côté picker ; borne 1900 portée par l'API (contrat l.48-49), non dupliquée côté app selon la doctrine « l'app ne valide rien » (DynamicForm.tsx:20-23).
5. **Plugin Expo** : `app.json` liste les plugins (app.json `expo.plugins`) ; savoir si `@react-native-community/datetimepicker` en requiert un : non vérifiable ici. Ajout d'un module natif → rebuild du client de dev nécessaire (connaissance générale).
6. **Exhaustivité TS** : `never` dans FormField.tsx:44 force le `case 'date'`.

## Ce que l'audit ne couvre pas

- Disponibilité d'`Intl.DateTimeFormat` sous Hermes : rien dans la source (moteur non configuré dans app.json → défaut Expo ; `node_modules/hermes-compiler` présent). Connaissance générale : Hermes fournit `Intl` (DateTimeFormat) sur iOS et Android depuis RN 0.70 ; sous Jest (Node) Intl est complet. **Non prouvé par exécution** — à confirmer dans le simulateur (critère 3 du contrat).
- API, props et mock officiel éventuel de `@react-native-community/datetimepicker` (non installé).
- Forme exacte de la définition `date` envoyée par l'API (placeholder, default) : relève de la tâche web.
- Pas de spec `spec_*.json` écrite : périmètre d'écriture limité au rapport par la tâche.
- Fichiers lus : form/types/index.ts, components/{FormField,FieldShell,DynamicForm,FormLoader(1-25)}.tsx, fields/* (8 fichiers), utils/values.ts, __tests__/DynamicForm.test.tsx ; auth/types/index.ts, components/{Avatar,AvatarPicker}.tsx, screens/{ProfileScreen,HomeScreen,PersonalInfoScreen,ProfileFormScreen}.tsx, services/PersonalInfoService.ts, utils/pick-avatar.ts, __tests__/{AvatarPicker,ProfileScreen}.test.tsx, ProfileFormScreen.test.tsx (1-30) ; shared/i18n/{messages,test-messages,locale,I18nContext}.ts(x), shared/network/connectivity.ts (1-20) + test, ApiClient.ts (grep), AuthProvider.tsx (grep) ; package.json, app.json. `.env` non lu.
