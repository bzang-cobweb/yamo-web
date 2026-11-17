# `2026-11-19` — Contrat du chantier accueil, description du profil et nombre de régions, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-19-accueil-description-regions.md`
  (4 points, avec des choix par défaut).
- **Réponse** : « ajouter la propriété nombre de caractères dans le type textarea par
  défaut 1000 et valider ».
  - **Description** sur l'utilisateur, dans les informations personnelles, non
    traduisible.
  - **Type textarea** : nombre maximal de caractères, 1 000 par défaut, validé par le
    serveur et envoyé à l'app (compteur, saisie bloquée au-delà).
  - **Réglages de l'application** (logo, description traduite) dans une page Filament,
    sans nouvelle dépendance ; nom = `APP_NAME`.
  - **Accueil** : premier écran de l'invité dans l'app ; la page web `/` montre logo
    ou nom et description, sans boutons.
  - **Connexion** de nouveau alignée en haut.
- **Motif** : un point d'entrée présentable de l'app, géré depuis le back-office.
- **Portée** : modules Auth, Form et Location, app Laravel, app mobile.
- **Corollaires** : tout textarea existant ou futur est limité à 1 000 caractères
  sauf réglage contraire.
- **Tranché par** : humain
