# `2026-11-27` — « Disponible dès le » juste sous « Type de location »

- **Type** : décision
- **Question posée** : « déplacer le filtre dès le en dessous du filtre type de location ; le faire dans tous les filtres ».
- **Ce qui est retenu** : dans les filtres des offres, « Disponible dès le » vient juste après « Type de location », dont il dépend (décision 2026-11-27-filtre-des-le-location), avant chambres et salles de bain. L'ordre vient de `RentalDomain::filters()`, commun à tous les filtres d'offres : la recherche, « Mes favoris », « Mes logements ». Les filtres des réservations n'ont pas ce champ.
- **Portée** : web, `RentalDomain::filters()` ; test d'ordre des filtres d'offres.
- **Tranché par** : humain
