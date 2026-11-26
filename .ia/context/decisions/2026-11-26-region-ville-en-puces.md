# `2026-11-26` — Région et ville en puces dans tous les filtres

Complète : 2026-11-25-ville-selon-region.md, 2026-11-25-region-obligatoire-recherche.md, 2026-11-26-ville-et-logement-apres-region.md

- **Type** : décision
- **Question posée** : « rendre les filtres region et ville en chips partout et la ville s'affiche toujours lorsqu'une région est sélectionnée ».
- **Ce qui est retenu** : dans les filtres de mes logements, des favoris et de Mes réservations, la région et la ville sont des puces, plusieurs à la fois ; dans la recherche, la ville aussi. La ville s'affiche dès qu'une région est choisie et propose les villes des régions choisies.
- **Exception, tranchée par l'humain** : la région de la recherche garde sa carte en tête de page, à choix unique, obligatoire, sans « Tous », par défaut la première région qui a des offres (décisions 2026-11-25-region-en-tete-recherche, 2026-11-25-region-obligatoire-recherche, 2026-11-25-region-sans-tous).
- **Corollaires** : une ville choisie doit appartenir à l'une des régions choisies, sinon le filtre ville est ignoré (`OptionBelongsToParent` accepte une ou plusieurs valeurs, pour l'option comme pour le parent). Un filtre masqué par sa condition est ignoré par la liste, puces comprises (`Field::isVisibleFor`, la règle `exclude_unless` ne lisant pas les listes). L'API attend des tableaux pour les puces (`filter[city][]=…`). Les champs région et ville des formulaires d'adresse ne changent pas.
- **Tranché par** : humain
