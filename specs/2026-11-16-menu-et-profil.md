# Contrat — Menu du bas et profil en trois formulaires

- **Chantier** : `2026-11-16-menu-et-profil`
- **Demande** (chat, 2026-11-16) : « Prévoir un bottom menu fixe qui a : une icône home
  pour aller sur la home (écran par défaut), une icône user pour aller sur le profile.
  Le profile doit afficher : la photo de profile, le nom et prénom, l'adresse e-mail,
  un lien informations personnelles (formulaire nom, prénom), un lien changer mot de
  passe (ancien, nouveau et confirmer), un lien préférences (formulaire locale).
  Chaque formulaire doit avoir son contrôleur et service Laravel et son service pour le
  mobile. »
- **Arbitrages tranchés** (chat, 2026-11-16) : icônes seules ; menu toujours visible
  une fois connecté, formulaires compris ; « Se déconnecter » en bas du profil ;
  changer le mot de passe déconnecte les autres appareils.
- **Statut** : validé le 2026-11-16 (décision `2026-11-16-contrat-menu-et-profil.md`)

## Ce que j'ai compris

### Mobile — navigation

- Un **menu fixe en bas**, à deux icônes : maison (Accueil, écran par défaut) et
  silhouette (Profil). Pas de libellé affiché ; le nom de l'onglet, servi par l'API,
  est annoncé aux lecteurs d'écran. L'onglet actif est mis en évidence.
- Le menu est présent sur tous les écrans d'un utilisateur connecté, y compris les trois
  formulaires du profil. L'écran de connexion et l'écran hors ligne n'en ont pas.
- Fourni par le routeur déjà en place (`expo-router/js-tabs`), icônes par
  `expo-symbols` : **aucune dépendance ajoutée**.
- **Accueil** : la photo et la salutation (« Bonjour … »). Le lien vers le profil et le
  bouton de déconnexion le quittent, puisque le menu et le profil les remplacent.

### Mobile — profil

- **En haut** : la photo de profil (toujours modifiable en la touchant), le prénom et le
  nom, l'adresse e-mail.
- **Trois liens** :
  - Informations personnelles ;
  - Changer le mot de passe ;
  - Préférences.

  Chacun ouvre son écran de formulaire, avec un lien Retour.
- **En bas** : « Se déconnecter ».
- Après un enregistrement réussi, l'app revient au profil avec un message de
  confirmation.

### Les trois formulaires (web et mobile)

Chaque formulaire est décrit par l'API (module `Form`). Il a un titre, son contrôleur
Laravel, son service Laravel, sa FormRequest (règles lues sur le formulaire), et un
service mobile avec une méthode par appel.

| Formulaire | Champs | Route | Laravel | Mobile |
|---|---|---|---|---|
| `auth.profile.personal` « Informations personnelles » | prénom, nom (obligatoires, 255 max.) | `PATCH /api/v1/auth/me/personal-info` | `PersonalInfoController`, `PersonalInfoService` | `PersonalInfoService.update` |
| `auth.profile.password` « Changer le mot de passe » | ancien, nouveau, confirmation | `PUT /api/v1/auth/me/password` | `PasswordController`, `PasswordService` | `PasswordService.update` |
| `auth.profile.preferences` « Préférences » | langue (avec son aide) | `PATCH /api/v1/auth/me/preferences` | `PreferencesController`, `PreferencesService` | `PreferencesService.update` |

- **Mot de passe** :
  - l'ancien doit être juste ;
  - le nouveau suit la règle de mot de passe de Laravel (`Password::defaults()`, 8
    caractères au moins), doit être confirmé et différent de l'ancien ;
  - en cas de succès, les jetons des **autres appareils** sont révoqués, l'appareil
    courant reste connecté ;
  - les messages d'erreur sont traduits.
- **Préférences** remplace le formulaire `auth.profile.locale` et la route
  `PATCH /api/v1/auth/me`, avec `ProfileController@update`, `ProfileService::update` et
  `UpdateMeRequest`. L'app mobile est le seul client de l'API, donc rien d'autre n'en
  dépend.
- Les trois routes sont gardées par `can:updateMe`, comme les autres routes du profil :
  chacun ne modifie que son propre compte.
- Les nouveaux textes de l'app (onglets, liens, confirmations) sont servis par l'API,
  en fr et en en.

## Découpage

1. Web : formulaires, contrôleurs, services, requêtes, routes, textes, tests →
   `qa-check` → commit `yamo-web`.
2. Mobile : onglets, accueil, profil, trois écrans de formulaire, trois services,
   tests → `qa-check` → commit `yamo-mobile`.
3. Vérification de bout en bout dans le simulateur iOS.

## Critères de succès

1. Connecté, un menu fixe en bas affiche deux icônes. La maison ouvre l'accueil (écran
   par défaut), la silhouette ouvre le profil. Le menu reste visible sur les
   formulaires du profil.
2. Le profil affiche la photo, le prénom et le nom, l'e-mail, les trois liens et
   « Se déconnecter ».
3. Informations personnelles : modifier le prénom et le nom les change partout dans
   l'app (accueil, profil) et en base. Un champ vide est refusé avec un message traduit
   sous le champ.
4. Mot de passe :
   - un ancien mot de passe faux est refusé sous son champ ;
   - un nouveau mot de passe non confirmé ou trop court est refusé ;
   - en cas de succès, on peut se connecter avec le nouveau mot de passe, l'appareil
     courant reste connecté, et les autres jetons sont révoqués.
5. Préférences : changer la langue change celle du compte et celle de l'app.
6. Chaque formulaire a son contrôleur, son service Laravel et son service mobile. Une
   méthode du service mobile correspond à un appel API.
7. L'ancienne route `PATCH /api/v1/auth/me` et le formulaire `auth.profile.locale`
   n'existent plus.
8. Les textes viennent de l'API (hors écran hors ligne) ; fr et en complets.
9. Tests web et mobile, typage, lint, Pint au vert. `RouteGuardTest` passe sans
   nouvelle exception. `openapi.json` est régénéré.

## Hors périmètre

Modifier l'e-mail ou le pseudonyme ; mot de passe oublié ; plus de deux onglets.
