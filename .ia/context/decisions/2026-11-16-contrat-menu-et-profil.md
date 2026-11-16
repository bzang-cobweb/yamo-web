# `2026-11-16` — Contrat du chantier menu du bas et profil, validé

Remplace : 2026-11-15-services-login-logout-profile.md (partie « PATCH /api/v1/auth/me », `ProfileService::update` et service mobile `update` uniquement ; le reste reste en vigueur)

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-16-menu-et-profil.md` et quatre arbitrages.
- **Réponse** :
  - **Menu du bas** : fixe, deux icônes seules (Accueil par défaut, Profil), le nom de
    l'onglet servi par l'API pour les lecteurs d'écran. Visible sur tous les écrans d'un
    utilisateur connecté, formulaires compris. Aucune dépendance (`expo-router/js-tabs`,
    `expo-symbols`).
  - **Profil** : photo, prénom et nom, e-mail, trois liens (Informations personnelles,
    Changer le mot de passe, Préférences), puis « Se déconnecter » en bas. L'accueil ne
    garde que la photo et la salutation.
  - **Un formulaire = un contrôleur et un service Laravel + un service mobile** :
    - `PersonalInfo` : `PATCH /api/v1/auth/me/personal-info` ;
    - `Password` : `PUT /api/v1/auth/me/password` ;
    - `Preferences` : `PATCH /api/v1/auth/me/preferences`.
  - **Préférences** remplace `auth.profile.locale` et `PATCH /api/v1/auth/me`, supprimés.
  - **Mot de passe** : changer de mot de passe révoque les jetons des autres appareils ;
    l'appareil courant reste connecté.
- **Motif** :
  - une navigation stable ;
  - un contexte par classe, côté serveur comme côté app ;
  - protéger un compte dont le mot de passe a fuité.
- **Portée** : module Auth (web), app mobile.
- **Corollaires** :
  - toute future section du profil suit le même schéma : formulaire, contrôleur,
    service, service mobile ;
  - `ProfileController` et `ProfileService` ne gardent que la consultation et la photo.
- **Tranché par** : humain
