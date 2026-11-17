# `2026-11-20` — Contrat du chantier accueil : nom complet, logo sur les onglets, choix de la langue, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-20-accueil-nom-langue.md`, et la présence du sélecteur de langue sur la page web `/`.
- **Réponse** : « Validated » ; sélecteur sur mobile **et** web.
  - **Onglet Accueil** : nom complet sous l'avatar.
  - **Barre du logo** : seulement sur les écrans racines des onglets, pas sur les formulaires du profil.
  - **Écran d'accueil de l'invité (mobile)** : sélecteur des langues de l'API. Le choix est mémorisé sur l'appareil, prime sur la langue de l'appareil et s'applique à l'inscription. Connecté, la langue du compte prime.
  - **Page web `/`** : liens de langue, choix gardé en session.
- **Motif** : laisser l'invité choisir sa langue avant d'avoir un compte.
- **Portée** : app mobile (auth, welcome), page d'accueil web.
- **Corollaires** : la langue choisie par l'invité devient celle du compte créé ensuite.
- **Tranché par** : humain
