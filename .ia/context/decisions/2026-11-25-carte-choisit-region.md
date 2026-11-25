# `2026-11-25` — Ouvrir une offre d'un carrousel de région choisit cette région dans la recherche

- **Type** : décision
- **Question posée** : « quand on clique sur le carousel dans la région, la recherche reprend cette région ».
- **Ce qui est retenu** :
  - Toucher une offre d'un carrousel de région de l'accueil ouvre sa fiche, comme avant. La recherche prend aussi cette région : en y allant, le sélecteur la montre et les offres la suivent.
  - Ses autres filtres restent. La ville s'efface si la région change (décision 2026-11-25-ville-selon-region).
  - « Voir tout » ouvre toujours la recherche sur la région seule.
  - Le bloc « À la une », qui mêle les régions, ne touche pas à la recherche.
  - `chooseFilter()` choisit une valeur de filtre et efface ceux qui en dépendent, pour le sélecteur de la recherche comme pour l'accueil.
- **Tranché par** : humain (la région reprise) ; session (« À la une » exclu, les autres filtres gardés)
