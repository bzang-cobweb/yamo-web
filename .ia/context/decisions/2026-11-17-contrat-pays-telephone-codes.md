# `2026-11-17` — Contrat du chantier pays, régions, téléphone et codes, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-17-pays-telephone-codes.md` (17 points,
  plus l'inscription), quatre arbitrages tranchés, quatre choix appliqués par défaut, et
  la précision sur l'indicatif.
- **Réponse** : validé tel quel, défauts compris.
  - **Connexion** : e-mail ou téléphone, mot de passe, **puis code**.
  - **SMS** : `SmsSender` interchangeable, implémentation qui écrit dans le journal pour
    l'instant.
  - **Dépendance autorisée** : `propaganistas/laravel-phone`.
  - **Première saisie** d'un e-mail ou d'un téléphone : lien « Ajouter » avec code,
    jamais d'identifiant non vérifié.
  - **Indicatif** : on choisit un pays, et l'indicatif est le `dial_code` de ce pays.
  - **Seed** : tous les pays (noms natif, fr et en), et les 26 cantons suisses.
  - **Statut en ligne** : servi par l'API et affiché dans la liste Filament.
  - **Inscription** : depuis l'app, avec e-mail et/ou téléphone, un code par
    identifiant saisi.
  - **Back-office Filament** : connexion inchangée (e-mail et mot de passe).
- **Motif** :
  - un compte joignable par e-mail ou par téléphone ;
  - des identifiants toujours vérifiés ;
  - une localisation de l'utilisateur.
- **Portée** : modules Auth, Form et Location (nouveau), app mobile.
- **Corollaires** :
  - un administrateur doit avoir un e-mail ;
  - le choix du fournisseur de SMS sera une décision à part ;
  - la décision `2026-11-16-contrat-genre-date-naissance.md` reste valable, sauf le
    genre, qui passe en liste déroulante.
- **Tranché par** : humain
