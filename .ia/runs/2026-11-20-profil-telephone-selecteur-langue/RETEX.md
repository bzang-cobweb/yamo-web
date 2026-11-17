# RETEX — 2026-11-20-profil-telephone-selecteur-langue

## Ce qui s'est produit deux fois

- Un contrat d'API modifié sans régénérer `openapi.json` aussitôt : ici, et déjà signalé par qa-check au chantier du 2026-11-20 (fil d'Ariane).
  - **Règle candidate** (projet) : toute modification d'une ressource d'API se termine par `ddev composer openapi`, avant tout qa-check.
- qa-check a remis un fichier suivi dans son état commité (`git checkout openapi.json`), ce qui a effacé un livrable non commité.
  - **Règle candidate** (marketplace, `bza-dev:qa-check`) : la vérification compare à un export jetable, et ne touche jamais un fichier suivi.

## Règles ré-implémentées / domiciliées

- Domiciliée : le format d'affichage d'un téléphone (`PhoneNumbers`, qui porte maintenant le national et l'international).
- Réutilisée : la fenêtre de choix des formulaires (`OptionsModal`), pour le sélecteur de langue.
