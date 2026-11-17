# RETEX — 2026-11-19-accueil-description-regions

- **Metro servait une ancienne version de `_layout.tsx`** malgré les relances d'Expo Go :
  15 minutes perdues à déboguer une redirection qui fonctionnait. Deuxième fois sur
  le projet (les routes typées l'avaient déjà montré) → règle candidate : après toute
  modification de `src/app/_layout.tsx`, relancer Metro avec `--clear` avant de
  vérifier dans le simulateur.
- **Une règle qui a trouvé un domicile** : la limite de longueur d'un texte long vit
  dans le type textarea (`Field::TEXTAREA_MAX_LENGTH`), réutilisée par le back-office
  (fiche utilisateur, réglages) au lieu d'un `max:1000` recopié.
- **Hook « id »** : `$u->id` dans une commande tinker est refusé par le garde-fou du
  plugin id ; utiliser `getKey()`.
