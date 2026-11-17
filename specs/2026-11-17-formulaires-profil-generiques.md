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
