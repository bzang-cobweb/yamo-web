# `2026-11-27` — Le filtre « Ville » en liste déroulante, partout

Remplace en partie : 2026-11-26-region-ville-en-puces.md (la ville en puces)

- **Type** : décision
- **Question posée** : « mettre le même pour le champ ville… partout ».
- **Ce qui est retenu** : le filtre « Ville » n'est plus en puces mais une liste déroulante avec recherche, dans le panneau de choix commun (décision 2026-11-27-select-images), comme « Logement » : dans les filtres des réservations (`FiltersBookings`) et des offres — la recherche, « Mes logements », les favoris (`FiltersOffers`). Une seule ville à la fois (`filter[city]=Douala`) ; toujours les villes de la région choisie, visible une région choisie. La région reste en puces. Les champs « Ville » des formulaires d'adresse restent une saisie libre.
- **Corollaire** : une ville envoyée en tableau (`filter[city][]=`, une ancienne version de l'app) n'est plus appliquée.
- **Portée** : web : `FiltersBookings`, `FiltersOffers`, tests des filtres d'offres et des favoris.
- **Tranché par** : humain
