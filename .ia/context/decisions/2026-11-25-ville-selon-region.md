# `2026-11-25` — Le filtre « Ville » suit la région

- **Type** : décision
- **Question posée** : « si on change de région ou de pays, le sélecteur ville s'efface ».
- **Ce qui est retenu** :
  - **Liste dépendante** : dans les listes d'offres et de réservations, le filtre « Ville » dépend de la région (`dependsOn('region')`).
    - Chaque ville a sa région pour parent.
    - Il ne propose que les villes de la région choisie, ou toutes les villes sans région choisie (« Mes logements », favoris).
  - **Changer de région efface la ville**, depuis le sélecteur de la recherche comme depuis l'écran des filtres. Changer de pays aussi (décision 2026-11-25-region-en-tete-recherche).
  - **Côté serveur** : une ville d'une autre région que celle choisie est ignorée. `Listing::validFilters()` vérifie maintenant chaque filtre avec les autres filtres reçus (le parent). Sans parent choisi, toute ville de la liste vaut.
- **Tranché par** : humain (la ville s'efface) ; session (la liste dépendante, la vérification côté serveur)
