# `2026-11-27` — « Disponible dès le » : seulement une location ou une colocation choisie

Remplace en partie : 2026-11-22-filtre-libre-des-sans-meuble.md (visible sans type de location choisi)

- **Type** : décision
- **Question posée** : « mes favoris filtres, le filtre dès le ne s'affiche que si le type de location est location ou colocation ».
- **Ce qui est retenu** : le filtre « Disponible dès le » (`available_from`) n'est visible que si le type de location choisi est « Location » ou « Colocation » (plusieurs choisis : tous l'un ou l'autre) ; sans type de location choisi, il est caché. Caché, il est ignoré par le serveur.
- **Portée** : web, `RentalDomain::filters()` : les filtres des offres du domaine location, donc « Mes favoris », la recherche et « Mes logements ». Tests des filtres d'offres adaptés.
- **Tranché par** : humain
