# Formulaires du profil génériques

Demande du 2026-11-17 : « le but est de rajouter un nouveau lien ProfileMenuKey sans modifier le mobile. Passer donc aussi l'identifiant du form et supprimer tous les screens et ne garder que ProfileFormScreen. »

## Ce que j'ai compris

Aujourd'hui, l'app a un écran par formulaire du profil : informations personnelles, e-mail, téléphone, mot de passe, préférences et suppression du compte. Chaque écran sait quel service appeler et quoi faire après l'envoi. Pour qu'un lien ajouté sur le serveur fonctionne sans nouvelle version de l'app, **le serveur doit décrire tout cela**, et l'app n'a plus qu'un écran générique.

### Serveur

- **Menu** (`GET /api/v1/auth/me/menu`) : chaque lien porte `key`, `label` et `form`, le nom du formulaire (`auth.profile.personal`…). `path` disparaît, puisque l'app construit la route depuis `form`.
- **Définition d'un formulaire** (`GET /api/v1/forms/{form}`) : `submit` décrit aussi l'envoi et sa suite.
  - `method` et `endpoint` : par exemple `PATCH` et `/v1/auth/me/personal-info`.
  - `destructive` : bouton rouge, pour la suppression du compte.
  - `success` :
    - soit `{ "action": "profile", "message": "Modifications enregistrées." }` : retour au profil, qui affiche le message ;
    - soit `{ "action": "sign-out", "message": "Votre compte a été supprimé." }` : message, puis déconnexion.
  - Un formulaire qui ne décrit pas d'envoi (connexion, inscription…) garde la forme actuelle de `submit`.
- **Titre** : celui du formulaire, déjà servi par l'API (« Ajouter » ou « Changer » selon le compte pour l'e-mail et le téléphone). Il s'affiche dans l'en-tête de l'écran.
- Ajouter un lien = un cas de `ProfileMenuKey` + son formulaire + son point d'API, **tout côté serveur**.

### App

- **Une seule route** : `/profile/[form]`, qui affiche `ProfileFormScreen`.
- `ProfileFormScreen` charge la définition, affiche son titre dans l'en-tête, puis envoie les valeurs à `endpoint` avec `method`. Selon la réponse :
  - **un code demandé** (réponse avec `challenge`) : étape du code, comme aujourd'hui pour l'e-mail et le téléphone ;
  - **un compte renvoyé** (`data` est l'utilisateur) : le compte est mis à jour dans l'app, et la langue change si elle a changé ;
  - **rien** (réponse vide) : rien à mettre à jour ;
  - **puis** `success` : retour au profil avec le message, ou message et déconnexion.
- **Supprimés** : `PersonalInfoScreen`, `EmailChangeScreen`, `PhoneChangeScreen`, `PasswordScreen`, `PreferencesScreen`, `DeleteAccountScreen` et leurs routes (`personal.tsx`, `email.tsx`…), ainsi que les services propres à ces formulaires.
- Le lien rouge « Supprimer mon compte » reste en bas du profil et ouvre `/profile/auth.profile.delete-account`.
- **Sécurité** :
  - l'app n'appelle qu'un `endpoint` relatif à l'API (`/v1/…`), avec le jeton de la session ;
  - un formulaire inconnu ou sans envoi décrit affiche une erreur, sans rien envoyer.

## Critères de succès

- Les six formulaires du profil marchent comme aujourd'hui, depuis un seul écran de l'app :
  - enregistrement ;
  - erreurs de validation sous les champs ;
  - code pour l'e-mail et le téléphone ;
  - langue qui bascule ;
  - suppression puis retour à l'accueil.
- Un test côté serveur ajoute un lien (cas d'énumération, formulaire, route), et l'app l'affiche puis l'envoie sans changement de code. Côté app, un test le prouve avec un formulaire qu'elle ne connaît pas.
- L'app ne contient plus de nom de formulaire du profil, sauf celui de la suppression du compte (lien fixe).
- Tests web et mobile verts, tsc, lint et Pint propres. OpenAPI régénéré. Aucune dépendance ajoutée.

## Questions ouvertes (défaut appliqué sans réponse)

1. **Suppression du compte** : elle passe aussi par l'écran générique, avec un lien rouge fixe en bas du profil. Défaut : oui. Autre option : la mettre dans le menu servi par l'API.
2. **Messages de confirmation** : le texte vient du serveur (`success.message`) au lieu des clés `notice` de l'app. Défaut : oui.

## Ce que j'ai décidé seul

- **Méthode et chemin lus sur la route nommée** (`Submission::toRoute()`) : le formulaire nomme sa route d'API, le serveur en déduit `method` et `endpoint`. Une seule source, pas de chemin recopié.
- **Messages de confirmation** : les clés existantes de `auth::mobile` (`profile.saved`, `profile.emailSaved`…, `auth.accountDeleted`) sont réutilisées côté serveur.
- **Confirmation gardée en mémoire, pas dans l'URL** : le profil ne lit plus de paramètre `notice`. Un lien venu d'ailleurs ne peut pas y faire écrire un texte.
- **Bouton rouge** : `DynamicForm` le lit dans `submit.destructive` ; la prop `destructive` de `FormLoader`/`DynamicForm` disparaît.
- **Contrôle de l'endpoint côté app** : `/v1` suivi de segments `[A-Za-z0-9_-]` seulement (ni `..`, ni `?`, ni adresse absolue), méthodes `POST`/`PATCH`/`PUT`/`DELETE`, suites `profile`/`sign-out`. Sinon : erreur, rien n'est envoyé.
- **Critère « un test ajoute un cas d'énumération »** : un cas d'enum PHP ne s'ajoute pas dans un test. Le test web déclare un formulaire et une route nouveaux (`test.nickname`) et vérifie qu'ils sont servis et envoyables ; un autre vérifie que chaque cas de `ProfileMenuKey` ouvre un formulaire envoyé à une route existante.

## Ce qui a été fait

- **Web** : `Modules/Form` — `SuccessAction`, `Submission`, `Form::submission()`, `submit` enrichi dans `FormResource`. Les six formulaires du profil décrivent leur envoi. `ProfileMenuKey::form()` remplace `path()` ; le menu sert `form`. Tests : `ProfileFormSubmissionApiTest` (nouveau), `ProfileMenuApiTest`. OpenAPI régénéré. 263 tests verts, Pint propre.
- **Mobile** : route unique `src/app/(tabs)/profile/[form].tsx`, `ProfileFormScreen` générique, `ProfileFormService`, `utils/profile-notice.ts`. Supprimés : six écrans, six routes, six services, `DeleteAccountScreen.test`. `ChallengeFlow.start` peut rendre `null` (fini sans code). README à jour. 119 tests verts, tsc et lint propres.
- **Simulateur iOS** : menu, formulaire e-mail ouvert par `/profile/[form]` avec le titre de l'API dans l'en-tête, erreur 422 sous le champ après un envoi vide, formulaire de suppression avec bouton rouge. Rien n'a été enregistré sur le compte.
- QA indépendante : GO, 0 bloquant (`.ia/runs/2026-11-17-formulaires-profil-generiques/01-qa-check.md`).

## Ce que je n'ai pas vérifié

- Un envoi réussi dans le simulateur (le compte de démonstration est le vrai compte super-admin) : enregistrement, code e-mail/téléphone, bascule de langue, suppression — couverts par les tests seulement.
- Le message après changement de langue : il est traduit dans la langue de la requête, donc l'ancienne langue, alors que l'app passe dans la nouvelle.
- Aucun test mobile ne vérifie la couleur du bouton rouge.

## Ce qui reste en suspens

- Rien d'ouvert sur le contrat. Le titre « Préférencess » vu plus tôt dans le simulateur n'est pas examiné.
