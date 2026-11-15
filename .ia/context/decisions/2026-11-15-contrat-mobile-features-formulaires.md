Remplacée par : 2026-11-15-services-login-logout-profile.md (arbitrage G uniquement)

# `2026-11-15` — Contrat du chantier « features mobile et formulaires pilotés par l'API » validé

- **Type** : décision
- **Question posée** : valider `specs/2026-11-15-mobile-features-formulaires.md` et ses arbitrages A à G.
- **Réponse** : validé avec les recommandations, et deux précisions de l'humain :
  - un seul composant `TextField` sert les types `text`, `hidden`, `email`, `password` (clavier, saisie masquée, champ non affiché selon le type) ;
  - un `switch` sur le `type` du champ (dans le rendu du formulaire) sert le bon composant.
  Arbitrages retenus : A (a) `src/shared/` ; B (a) `text`, `hidden`, `email`, `password`, `number`, `textarea`, `select`, `radio`, `checkbox`, `checkbox_group`, `switch` — `date` plus tard ; C (a) module `Form` + formulaires dans leur module ; D (b) `device_name` ajouté par l'app, hors définition ; E dépendances autorisées : `jest-expo`, `jest`, `@testing-library/react-native`, `@types/jest` (dev), `expo-localization` ; F (a) `expo-localization` ; G (b) feature `profile` avec `ProfileService`.
- **Motif** : le formulaire mobile n'affiche que ce que l'API décrit ; une seule source pour les champs et les règles ; langue de l'app = langue du compte, sinon la meilleure langue de l'appareil.
- **Portée** : ce chantier (mobile et API des formulaires, langues, modification de la langue du compte).
- **Corollaires** : `hidden` existe comme type de champ, rendu sans affichage ; ajouter un type = un cas dans le `switch` + son composant (ou un type de plus servi par `TextField`).
- **Tranché par** : humain
