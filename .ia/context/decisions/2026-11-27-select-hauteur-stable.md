# `2026-11-27` — Panneau de choix : la recherche ne réduit pas sa hauteur

- **Type** : décision
- **Question posée** : « le problème c'est que la recherche diminue la taille de la zone de contenu dans le panneau ».
- **Ce qui est retenu** : le panneau de choix (`OptionsModal`, à la hauteur de son contenu, décision 2026-11-27-select-images) garde la hauteur qu'il a à l'ouverture, toutes les options affichées : moins d'options trouvées par la recherche ne le font plus rapetisser. Mesurée sans recherche, cette hauteur ne fait que grandir ; elle repart de zéro à la fermeture.
- **Portée** : mobile, `OptionsModal` (toutes les listes déroulantes).
- **Tranché par** : humain
