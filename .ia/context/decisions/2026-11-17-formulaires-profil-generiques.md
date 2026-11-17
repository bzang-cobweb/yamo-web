# `2026-11-17` — Formulaires du profil génériques : le serveur décrit l'envoi, l'app n'a qu'un écran

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-17-formulaires-profil-generiques.md`, avec deux questions ouvertes (suppression du compte par l'écran générique ; message de confirmation servi par le serveur).
- **Réponse** : « Validated » — contrat accepté tel quel, défauts compris.
  - Le menu du profil sert `key`, `label` et `form` ; `path` disparaît.
  - Le `submit` d'un formulaire du profil décrit `method`, `endpoint`, `destructive` et `success` (`profile` ou `sign-out`, avec `message`).
  - L'app n'a qu'une route `/profile/[form]` et un `ProfileFormScreen` ; les six écrans, leurs routes et leurs services sont supprimés.
  - La suppression du compte passe par l'écran générique, avec un lien rouge fixe vers `auth.profile.delete-account`.
  - Les messages de confirmation viennent du serveur.
- **Motif** : ajouter un lien au menu du profil sans nouvelle version de l'app.
- **Portée** : module Auth (menu, formulaires du profil), module Form (`submit`), app mobile (profil).
- **Corollaires** :
  - l'app n'appelle qu'un `endpoint` relatif `/v1/…` ;
  - un nouveau formulaire du profil ne peut utiliser que les suites connues de l'app (code de vérification, compte renvoyé, `profile`, `sign-out`) ; une suite nouvelle demande une version de l'app ;
  - remplace le corollaire « pas de l'app tant que les écrans existent » de `2026-11-17-regions-alphabetiques-menu-en-code.md`.
- **Tranché par** : humain
