# `2026-11-18` — Items de liste : une bordure, sans fond

- **Type** : décision
- **Question posée** : aucune. Consigne de l'humain : « enlever le background et mettre une bordure dans le rendu d'un ListItem ».
- **Ce qui est retenu** :
  - les items des listes de l'app n'ont plus de fond. Ils ont une bordure fine (1 px), de la couleur `backgroundSelected` du thème, et des coins arrondis. Cela vaut pour la vitrine des offres comme pour les listes du profil ;
  - l'emplacement d'une photo absente garde un fond, pour rester visible sur une carte sans fond.
- **Motif** : un rendu plus léger, centré sur les photos.
- **Portée** : app mobile (`BrowseScreen`, `ProfileListScreen`, `ItemPhoto`).
- **Tranché par** : humain
